<?php

namespace App\Services;

use App\Enums\ShippingMethod;
use App\Models\Cart;
use App\Models\CartItem;

class ShippingCalculatorService
{
    public function __construct(
        private readonly ShippingSettingsService $settings,
    ) {}

    /**
     * @return array{
     *     shipping: float,
     *     shipping_rate: float,
     *     free_shipping_threshold: float,
     *     qualifies_for_free_shipping: bool,
     *     method: string|null
     * }
     */
    public function calculate(Cart $cart, float $discountedSubtotal): array
    {
        $threshold = $this->settings->freeShippingThreshold();
        $qualifiesForFreeShipping = $threshold > 0 && $discountedSubtotal >= $threshold;

        if ($qualifiesForFreeShipping) {
            return [
                'shipping' => 0.0,
                'shipping_rate' => 0.0,
                'free_shipping_threshold' => $threshold,
                'qualifies_for_free_shipping' => true,
                'method' => 'free',
            ];
        }

        $method = $this->settings->defaultMethod();

        $shipping = round(match ($method) {
            ShippingMethod::Product => $this->calculateProductBased($cart),
            ShippingMethod::Category => $this->calculateCategoryBased($cart),
            ShippingMethod::Flat => $this->settings->flatRateAmount(),
        }, 2);

        return [
            'shipping' => $shipping,
            'shipping_rate' => $shipping,
            'free_shipping_threshold' => $threshold,
            'qualifies_for_free_shipping' => false,
            'method' => $method->value,
        ];
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
        if (isset($productRates[$item->product_id])) {
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
