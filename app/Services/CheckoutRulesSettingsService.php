<?php

namespace App\Services;

use App\Models\CheckoutRulesSetting;
use Illuminate\Support\Facades\Cache;

class CheckoutRulesSettingsService
{
    private const CACHE_KEY = 'checkout_rules.settings.resolved';

    public function settings(): CheckoutRulesSetting
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): CheckoutRulesSetting {
            return CheckoutRulesSetting::current();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function minimumOrderEnabled(): bool
    {
        return (bool) $this->settings()->minimum_order_enabled;
    }

    public function minimumOrderAmount(): float
    {
        if (! $this->minimumOrderEnabled()) {
            return 0.0;
        }

        return (float) $this->settings()->minimum_order_amount;
    }

    public function couponsEnabled(): bool
    {
        return (bool) $this->settings()->coupons_enabled;
    }

    /** @param  array<string, mixed>  $data */
    public function sync(array $data): CheckoutRulesSetting
    {
        $settings = CheckoutRulesSetting::current();
        $settings->update($data);
        $this->clearCache();

        return $settings->fresh();
    }
}
