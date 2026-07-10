<?php

namespace App\Services;

use App\Enums\ShippingMethod;
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
        Cache::forget('shipping.category_rates');
    }

    /** @return array<int, float> */
    public function activeProductRates(): array
    {
        return Cache::remember('shipping.product_rates', now()->addHour(), function (): array {
            return ProductShippingRate::query()
                ->where('is_active', true)
                ->pluck('amount', 'product_id')
                ->map(fn ($amount) => (float) $amount)
                ->all();
        });
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

    public function freeShippingThreshold(): float
    {
        $settings = $this->settings();

        if (! $settings->free_shipping_enabled) {
            return 0.0;
        }

        return (float) $settings->free_shipping_threshold;
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
        $settings = $this->settings();
        $method = $settings->default_method ?? ShippingMethod::Flat;

        if ($this->isMethodEnabled($method)) {
            return $method;
        }

        foreach ([ShippingMethod::Product, ShippingMethod::Category, ShippingMethod::Flat] as $fallback) {
            if ($this->isMethodEnabled($fallback)) {
                return $fallback;
            }
        }

        return ShippingMethod::Flat;
    }

    public function isMethodEnabled(ShippingMethod $method): bool
    {
        $settings = $this->settings();

        return match ($method) {
            ShippingMethod::Flat => (bool) $settings->flat_rate_enabled,
            ShippingMethod::Product => (bool) $settings->product_rate_enabled,
            ShippingMethod::Category => (bool) $settings->category_rate_enabled,
        };
    }

    /**
     * @param  array<string, mixed>  $settingsData
     * @param  list<array{product_id: int, amount: float, is_active?: bool}>  $productRates
     * @param  list<array{category_id: int, amount: float, is_active?: bool}>  $categoryRates
     */
    public function sync(array $settingsData, array $productRates, array $categoryRates): ShippingSetting
    {
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
