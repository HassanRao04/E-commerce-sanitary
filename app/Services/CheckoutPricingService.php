<?php

namespace App\Services;

use App\Models\Cart;

/**
 * Backward-compatible facade over {@see CheckoutRulesEngine}.
 */
class CheckoutPricingService
{
    public function __construct(
        private readonly CheckoutRulesEngine $rulesEngine,
    ) {}

    /**
     * @return array{
     *     subtotal: float,
     *     discount: float,
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
     *     shipping_method: string|null,
     *     coupon_code: string|null
     * }
     */
    public function calculate(Cart $cart): array
    {
        $result = $this->rulesEngine->calculate($cart);

        return collect($result)->only([
            'subtotal',
            'discount',
            'shipping',
            'service_charge',
            'handling_charge',
            'tax',
            'grand_total',
            'tax_rate',
            'tax_type',
            'tax_label',
            'shipping_rate',
            'free_shipping_threshold',
            'qualifies_for_free_shipping',
            'shipping_method',
            'coupon_code',
            'qualifies_for_free_shipping',
        ])->all();
    }
}
