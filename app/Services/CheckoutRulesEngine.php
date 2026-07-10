<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Validation\ValidationException;

class CheckoutRulesEngine
{
    public function __construct(
        private readonly CheckoutRulesSettingsService $checkoutRulesSettings,
        private readonly ShippingSettingsService $shippingSettings,
        private readonly ShippingCalculatorService $shippingCalculator,
        private readonly TaxChargeSettingsService $taxChargeSettings,
        private readonly TaxChargeCalculatorService $taxChargeCalculator,
    ) {}

    /**
     * Calculate checkout totals from ERP rules.
     *
     * Formula: Subtotal + Shipping + Service + Handling + Tax − Discount = Grand Total
     *
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     discounted_subtotal: float,
     *     shipping: float,
     *     service_charge: float,
     *     handling_charge: float,
     *     tax: float,
     *     grand_total: float,
     *     tax_rate: float,
     *     tax_type: string|null,
     *     tax_label: string,
     *     shipping_rate: float,
     *     free_shipping_threshold: float,
     *     qualifies_for_free_shipping: bool,
     *     amount_until_free_shipping: float|null,
     *     shipping_method: string|null,
     *     coupon_code: string|null,
     *     minimum_order_amount: float,
     *     minimum_order_met: bool,
     *     coupons_enabled: bool,
     *     rules: array<string, mixed>
     * }
     */
    public function calculate(Cart $cart): array
    {
        $cart->loadMissing(['items', 'coupon']);

        $subtotal = $this->cartSubtotal($cart);
        $discount = $this->resolveDiscount($cart, $subtotal);
        $discountedSubtotal = max(0, round($subtotal - $discount, 2));

        $shippingResult = $this->shippingCalculator->calculate($cart, $discountedSubtotal);
        $chargeResult = $this->taxChargeCalculator->calculate(
            $discountedSubtotal,
            $shippingResult['shipping'],
        );

        $grandTotal = round(
            $discountedSubtotal
            + $shippingResult['shipping']
            + $chargeResult['service_charge']
            + $chargeResult['handling_charge']
            + $chargeResult['tax'],
            2
        );

        $freeShippingThreshold = $shippingResult['free_shipping_threshold'];
        $minimumOrderAmount = $this->checkoutRulesSettings->minimumOrderAmount();

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discounted_subtotal' => $discountedSubtotal,
            'shipping' => $shippingResult['shipping'],
            'service_charge' => $chargeResult['service_charge'],
            'handling_charge' => $chargeResult['handling_charge'],
            'tax' => $chargeResult['tax'],
            'grand_total' => $grandTotal,
            'tax_rate' => $chargeResult['tax_rate'],
            'tax_type' => $chargeResult['tax_type'],
            'tax_label' => $chargeResult['tax_label'],
            'shipping_rate' => $shippingResult['shipping_rate'],
            'free_shipping_threshold' => $freeShippingThreshold,
            'qualifies_for_free_shipping' => $shippingResult['qualifies_for_free_shipping'],
            'amount_until_free_shipping' => $this->amountUntilFreeShipping(
                $discountedSubtotal,
                $freeShippingThreshold,
                $shippingResult['qualifies_for_free_shipping'],
            ),
            'shipping_method' => $shippingResult['method'],
            'coupon_code' => $discount > 0 ? $cart->coupon?->code : null,
            'minimum_order_amount' => $minimumOrderAmount,
            'minimum_order_met' => $this->minimumOrderMet($subtotal),
            'coupons_enabled' => $this->checkoutRulesSettings->couponsEnabled(),
            'rules' => $this->rulesSnapshot(),
        ];
    }

    public function validateForCheckout(Cart $cart): void
    {
        $cart->loadMissing(['items', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $subtotal = $this->cartSubtotal($cart);

        if (! $this->minimumOrderMet($subtotal)) {
            throw ValidationException::withMessages([
                'cart' => 'Minimum order amount of '
                    .config('shop.currency_symbol')
                    .number_format($this->checkoutRulesSettings->minimumOrderAmount(), 2)
                    .' required.',
            ]);
        }

        if (! $this->checkoutRulesSettings->couponsEnabled() && $cart->coupon_id) {
            throw ValidationException::withMessages([
                'cart' => 'Coupons are not available at this time.',
            ]);
        }

        if ($cart->coupon && $this->checkoutRulesSettings->couponsEnabled()) {
            $discount = $this->resolveDiscount($cart, $subtotal);

            if ($discount <= 0) {
                $message = $cart->coupon->min_order_amount
                    ? 'Coupon requires a minimum order of '
                        .config('shop.currency_symbol')
                        .number_format((float) $cart->coupon->min_order_amount, 2).'.'
                    : 'The applied coupon is no longer valid for this cart.';

                throw ValidationException::withMessages(['cart' => $message]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rulesSnapshot(): array
    {
        $shipping = $this->shippingSettings->settings();
        $tax = $this->taxChargeSettings->settings();
        $checkout = $this->checkoutRulesSettings->settings();

        return [
            'checkout' => [
                'minimum_order_enabled' => (bool) $checkout->minimum_order_enabled,
                'minimum_order_amount' => (float) $checkout->minimum_order_amount,
                'coupons_enabled' => (bool) $checkout->coupons_enabled,
            ],
            'shipping' => [
                'flat_rate_enabled' => (bool) $shipping->flat_rate_enabled,
                'flat_rate_amount' => (float) $shipping->flat_rate_amount,
                'product_rate_enabled' => (bool) $shipping->product_rate_enabled,
                'category_rate_enabled' => (bool) $shipping->category_rate_enabled,
                'free_shipping_enabled' => (bool) $shipping->free_shipping_enabled,
                'free_shipping_threshold' => $this->shippingSettings->freeShippingThreshold(),
                'default_method' => $shipping->default_method?->value,
            ],
            'tax' => [
                'tax_type' => $this->taxChargeSettings->activeTaxType()?->value,
                'tax_rate' => $this->taxChargeSettings->activeTaxRate(),
                'tax_label' => $this->taxChargeSettings->activeTaxLabel(),
                'service_charge_enabled' => (bool) $tax->service_charge_enabled,
                'handling_charge_enabled' => (bool) $tax->handling_charge_enabled,
            ],
            'coupons' => [
                'enabled' => $this->checkoutRulesSettings->couponsEnabled(),
                'managed_in' => 'coupons',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function storefrontContext(): array
    {
        $threshold = $this->shippingSettings->freeShippingThreshold();
        $shipping = $this->shippingSettings->settings();

        return [
            'free_shipping_enabled' => (bool) $shipping->free_shipping_enabled,
            'free_shipping_threshold' => $threshold,
            'minimum_order_enabled' => $this->checkoutRulesSettings->minimumOrderEnabled(),
            'minimum_order_amount' => $this->checkoutRulesSettings->minimumOrderAmount(),
            'coupons_enabled' => $this->checkoutRulesSettings->couponsEnabled(),
        ];
    }

    public function cartSubtotal(Cart $cart): float
    {
        return round($cart->items->sum(
            fn (CartItem $item): float => (float) $item->unit_price * $item->quantity
        ), 2);
    }

    public function resolveDiscount(Cart $cart, float $subtotal): float
    {
        if (! $this->checkoutRulesSettings->couponsEnabled()) {
            return 0.0;
        }

        if (! $cart->coupon || ! $cart->coupon->is_valid) {
            return 0.0;
        }

        return $cart->coupon->calculateDiscount($subtotal);
    }

    public function minimumOrderMet(float $subtotal): bool
    {
        if (! $this->checkoutRulesSettings->minimumOrderEnabled()) {
            return true;
        }

        return $subtotal >= $this->checkoutRulesSettings->minimumOrderAmount();
    }

    private function amountUntilFreeShipping(
        float $discountedSubtotal,
        float $threshold,
        bool $qualifies,
    ): ?float {
        if ($threshold <= 0 || $qualifies) {
            return null;
        }

        return round(max(0, $threshold - $discountedSubtotal), 2);
    }
}
