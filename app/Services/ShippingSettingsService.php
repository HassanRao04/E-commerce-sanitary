<?php

namespace App\Services;

use App\Enums\ShippingMethod;
use App\Models\Cart;
use App\Models\CategoryShippingRate;
use App\Models\ProductShippingRate;
use App\Models\ShippingSetting;
use Illuminate\Support\Facades\Cache;

class ShippingSettingsService
{
    private const CACHE_KEY = 'shipping.settings.resolved';

    public function settings(): ShippingSetting
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): ShippingSetting {
            return ShippingSetting::current();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('shipping.product_rates');
        Cache::forget('shipping.product_rate_details');
        Cache::forget('shipping.category_rates');
    }

    /**
     * Active product delivery charges (free-shipping products resolve to 0).
     *
     * @return array<int, float>
     */
    public function activeProductRates(): array
    {
        return collect($this->activeProductRateDetails())
            ->map(fn (array $detail): float => $detail['free_shipping'] ? 0.0 : $detail['amount'])
            ->all();
    }

    /**
     * @return array<int, array{amount: float, free_shipping: bool}>
     */
    public function activeProductRateDetails(): array
    {
        return Cache::remember('shipping.product_rate_details', now()->addHour(), function (): array {
            return ProductShippingRate::query()
                ->where('is_active', true)
                ->get(['product_id', 'amount', 'free_shipping'])
                ->mapWithKeys(fn (ProductShippingRate $rate): array => [
                    (int) $rate->product_id => [
                        'amount' => (float) $rate->amount,
                        'free_shipping' => (bool) $rate->free_shipping,
                    ],
                ])
                ->all();
        });
    }

    /**
     * Scenario 5: every line in the cart has Free Shipping = Yes on its product rate.
     */
    public function cartQualifiesForProductFreeShipping(Cart $cart): bool
    {
        $cart->loadMissing('items');

        if ($cart->items->isEmpty()) {
            return false;
        }

        $details = $this->activeProductRateDetails();

        foreach ($cart->items as $item) {
            $detail = $details[$item->product_id] ?? null;

            if (! $detail || ! $detail['free_shipping']) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, float> */
    public function activeCategoryRates(): array
    {
        return Cache::remember('shipping.category_rates', now()->addHour(), function (): array {
            return CategoryShippingRate::query()
                ->where('is_active', true)
                ->pluck('amount', 'category_id')
                ->map(fn ($amount) => (float) $amount)
                ->all();
        });
    }

    public function isFreeShippingEnabled(): bool
    {
        return (bool) $this->settings()->free_shipping_enabled;
    }

    /**
     * Threshold used for qualification and storefront messaging.
     * Returns 0 when free shipping is disabled so callers never treat it as active.
     */
    public function freeShippingThreshold(): float
    {
        if (! $this->isFreeShippingEnabled()) {
            return 0.0;
        }

        return (float) $this->settings()->free_shipping_threshold;
    }

    /**
     * Configured threshold from ERP (even when free shipping is disabled).
     * Use {@see freeShippingThreshold()} for checkout qualification display.
     */
    public function configuredFreeShippingThreshold(): float
    {
        return (float) $this->settings()->free_shipping_threshold;
    }

    public function flatRateAmount(): float
    {
        $settings = $this->settings();

        if (! $settings->flat_rate_enabled) {
            return 0.0;
        }

        return (float) $settings->flat_rate_amount;
    }

    public function defaultMethod(): ShippingMethod
    {
        return $this->settings()->default_method ?? ShippingMethod::Flat;
    }

    public function isMethodEnabled(ShippingMethod $method): bool
    {
        $settings = $this->settings();
        $flag = $method->enabledSettingKey();

        return $flag !== null && (bool) $settings->{$flag};
    }

    /**
     * Keep exactly one charge method active — the ERP default_method.
     * Free shipping remains an independent rule and is not affected.
     *
     * @param  array<string, mixed>  $settingsData
     * @return array<string, mixed>
     */
    public function normalizeMethodFlags(array $settingsData): array
    {
        $method = $settingsData['default_method'] ?? ShippingMethod::Flat;

        if (! $method instanceof ShippingMethod) {
            $method = ShippingMethod::from((string) $method);
        }

        $settingsData['default_method'] = $method;

        foreach (ShippingMethod::cases() as $case) {
            $flag = $case->enabledSettingKey();

            if ($flag !== null) {
                $settingsData[$flag] = $method === $case;
            }
        }

        return $settingsData;
    }

    /**
     * @param  array<string, mixed>  $settingsData
     * @param  list<array{product_id: int, amount: float, is_active?: bool, free_shipping?: bool}>  $productRates
     * @param  list<array{category_id: int, amount: float, is_active?: bool}>  $categoryRates
     */
    public function sync(array $settingsData, array $productRates, array $categoryRates): ShippingSetting
    {
        $settingsData = $this->normalizeMethodFlags($settingsData);

        $settings = ShippingSetting::current();
        $settings->update($settingsData);

        $productIds = [];
        foreach ($productRates as $row) {
            if (empty($row['product_id']) || ! isset($row['amount'])) {
                continue;
            }

            ProductShippingRate::updateOrCreate(
                ['product_id' => (int) $row['product_id']],
                [
                    'amount' => $row['amount'],
                    'free_shipping' => ! empty($row['free_shipping']),
                    'is_active' => ! empty($row['is_active']),
                ],
            );

            $productIds[] = (int) $row['product_id'];
        }

        ProductShippingRate::query()
            ->when($productIds !== [], fn ($query) => $query->whereNotIn('product_id', $productIds))
            ->when($productIds === [], fn ($query) => $query)
            ->delete();

        $categoryIds = [];
        foreach ($categoryRates as $row) {
            if (empty($row['category_id']) || ! isset($row['amount'])) {
                continue;
            }

            CategoryShippingRate::updateOrCreate(
                ['category_id' => (int) $row['category_id']],
                [
                    'amount' => $row['amount'],
                    'is_active' => ! empty($row['is_active']),
                ],
            );

            $categoryIds[] = (int) $row['category_id'];
        }

        CategoryShippingRate::query()
            ->when($categoryIds !== [], fn ($query) => $query->whereNotIn('category_id', $categoryIds))
            ->when($categoryIds === [], fn ($query) => $query)
            ->delete();

        $this->clearCache();

        return $settings->fresh();
    }
}
