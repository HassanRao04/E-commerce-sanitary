<?php

namespace App\Services;

use App\Enums\ShippingMethod;
use App\Models\Cart;
use App\Models\CartItem;

class ShippingCalculatorService
{
    public function __construct(
        private readonly ShippingSettingsService $settings,
        private readonly ProductOfferCalculatorService $productOffers,
    ) {}

    /**
     * @return array{
     *     shipping: float,
     *     shipping_rate: float,
     *     shipping_discount: float,
     *     free_shipping_threshold: float,
     *     qualifies_for_free_shipping: bool,
     *     method: string|null
     * }
     */
    public function calculate(Cart $cart, float $discountedSubtotal): array
    {
        $threshold = $this->settings->freeShippingThreshold();
        $method = $this->settings->defaultMethod();
        $grossShipping = $this->calculateForMethod($method, $cart);

        if ($waiver = $this->resolveFreeShippingWaiver($cart, $discountedSubtotal)) {
            return [
                'shipping' => 0.0,
                'shipping_rate' => 0.0,
                'shipping_discount' => $grossShipping,
                'free_shipping_threshold' => $threshold,
                'qualifies_for_free_shipping' => true,
                'method' => $waiver,
            ];
        }

        return [
            'shipping' => $grossShipping,
            'shipping_rate' => $grossShipping,
            'shipping_discount' => 0.0,
            'free_shipping_threshold' => $threshold,
            'qualifies_for_free_shipping' => false,
            'method' => $method->value,
        ];
    }

    /**
     * Free-shipping rules (evaluated before the selected charge method).
     * Returns a method label when shipping is waived, otherwise null.
     */
    private function resolveFreeShippingWaiver(Cart $cart, float $orderTotal): ?string
    {
        // Scenario 4 — order total threshold
        if (
            $this->settings->isFreeShippingEnabled()
            && $orderTotal >= $this->settings->configuredFreeShippingThreshold()
        ) {
            return 'free';
        }

        // Scenario 5 — every cart product is marked Free Shipping = Yes
        if ($this->settings->cartQualifiesForProductFreeShipping($cart)) {
            return 'free_product';
        }

        // Product offer Buy-X free shipping
        if ($this->productOffers->cartQualifiesForOfferFreeShipping($cart)) {
            return 'free_offer';
        }

        return null;
    }

    /**
     * Charge methods registry. Add a ShippingMethod case + calculator here
     * (and an enable flag on ShippingSetting) to extend without rewriting checkout.
     *
     * @return array<string, callable(Cart): float>
     */
    protected function methodCalculators(): array
    {
        return [
            ShippingMethod::Flat->value => fn (Cart $cart): float => $this->settings->flatRateAmount(),
            ShippingMethod::Product->value => fn (Cart $cart): float => $this->calculateProductBased($cart),
            ShippingMethod::Category->value => fn (Cart $cart): float => $this->calculateCategoryBased($cart),
        ];
    }

    private function calculateForMethod(ShippingMethod $method, Cart $cart): float
    {
        $calculators = $this->methodCalculators();
        $calculator = $calculators[$method->value]
            ?? $calculators[ShippingMethod::Flat->value];

        return round($calculator($cart), 2);
    }

    private function calculateProductBased(Cart $cart): float
    {
        $cart->loadMissing(['items.product.categories']);
        $productRates = $this->settings->activeProductRates();
        $categoryRates = $this->settings->activeCategoryRates();
        $flatFallback = $this->settings->flatRateAmount();
        $total = 0.0;
        $matched = false;

        foreach ($cart->items as $item) {
            $lineRate = $this->resolveProductLineRate($item, $productRates, $categoryRates);
            if ($lineRate !== null) {
                $matched = true;
                $total += $lineRate * $item->quantity;
            }
        }

        if ($matched) {
            return $total;
        }

        return $flatFallback;
    }

    private function calculateCategoryBased(Cart $cart): float
    {
        $cart->loadMissing(['items.product.categories']);
        $categoryRates = $this->settings->activeCategoryRates();
        $flatFallback = $this->settings->flatRateAmount();
        $total = 0.0;
        $matched = false;

        foreach ($cart->items as $item) {
            $lineRate = $this->resolveCategoryLineRate($item, $categoryRates);
            if ($lineRate !== null) {
                $matched = true;
                $total += $lineRate * $item->quantity;
            }
        }

        if ($matched) {
            return $total;
        }

        return $flatFallback;
    }

    /**
     * @param  array<int, float>  $productRates
     * @param  array<int, float>  $categoryRates
     */
    private function resolveProductLineRate(CartItem $item, array $productRates, array $categoryRates): ?float
    {
        if (array_key_exists($item->product_id, $productRates)) {
            return $productRates[$item->product_id];
        }

        return $this->resolveCategoryLineRate($item, $categoryRates);
    }

    /** @param  array<int, float>  $categoryRates */
    private function resolveCategoryLineRate(CartItem $item, array $categoryRates): ?float
    {
        $product = $item->product;

        if (! $product) {
            return null;
        }

        foreach ($product->categories as $category) {
            if (isset($categoryRates[$category->id])) {
                return $categoryRates[$category->id];
            }
        }

        return null;
    }
}
