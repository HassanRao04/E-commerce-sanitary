<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;

class CheckoutPricingService
{
    /**
     * Calculate checkout totals for a cart.
     *
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     shipping: float,
     *     tax: float,
     *     grand_total: float,
     *     tax_rate: float,
     *     shipping_rate: float,
     *     free_shipping_threshold: float,
     *     qualifies_for_free_shipping: bool,
     *     coupon_code: string|null
     * }
     */
    public function calculate(Cart $cart): array
    {
        $cart->loadMissing(['items', 'coupon']);

        $subtotal = round($cart->items->sum(
            fn (CartItem $item): float => (float) $item->unit_price * $item->quantity
        ), 2);

        $discount = 0.0;

        if ($cart->coupon && $cart->coupon->is_valid) {
            $discount = $cart->coupon->calculateDiscount($subtotal);
        }

        $discountedSubtotal = max(0, $subtotal - $discount);
        $freeShippingThreshold = (float) config('shop.free_shipping_threshold', 0);
        $shippingRate = (float) config('shop.shipping_flat_rate', 0);
        $qualifiesForFreeShipping = $freeShippingThreshold > 0
            && $discountedSubtotal >= $freeShippingThreshold;

        $shipping = $qualifiesForFreeShipping ? 0.0 : $shippingRate;

        $taxRate = (float) config('shop.tax_rate', 0);
        $taxableAmount = $discountedSubtotal + $shipping;
        $tax = round($taxableAmount * ($taxRate / 100), 2);

        $grandTotal = round($discountedSubtotal + $shipping + $tax, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'tax_rate' => $taxRate,
            'shipping_rate' => $shippingRate,
            'free_shipping_threshold' => $freeShippingThreshold,
            'qualifies_for_free_shipping' => $qualifiesForFreeShipping,
            'coupon_code' => $cart->coupon?->code,
        ];
    }
}
