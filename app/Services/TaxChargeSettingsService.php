<?php

namespace App\Services;

use App\Enums\ChargeCalculationType;
use App\Enums\TaxType;
use App\Models\TaxChargeSetting;
use Illuminate\Support\Facades\Cache;

class TaxChargeSettingsService
{
    private const CACHE_KEY = 'tax.charge.settings.resolved';

    public function settings(): TaxChargeSetting
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): TaxChargeSetting {
            return TaxChargeSetting::current();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function activeTaxType(): ?TaxType
    {
        $settings = $this->settings();
        $type = $settings->default_tax_type ?? TaxType::None;

        if ($type === TaxType::None || ! $this->isTaxTypeEnabled($type)) {
            return null;
        }

        return $type;
    }

    public function activeTaxRate(): float
    {
        $type = $this->activeTaxType();

        if (! $type) {
            return 0.0;
        }

        $settings = $this->settings();

        return (float) match ($type) {
            TaxType::Vat => $settings->vat_rate,
            TaxType::Gst => $settings->gst_rate,
            TaxType::SalesTax => $settings->sales_tax_rate,
            TaxType::None => 0,
        };
    }

    public function activeTaxLabel(): string
    {
        return $this->activeTaxType()?->label() ?? 'Tax';
    }

    public function isTaxTypeEnabled(TaxType $type): bool
    {
        $settings = $this->settings();

        return match ($type) {
            TaxType::Vat => (bool) $settings->vat_enabled,
            TaxType::Gst => (bool) $settings->gst_enabled,
            TaxType::SalesTax => (bool) $settings->sales_tax_enabled,
            TaxType::None => false,
        };
    }

    /** @param  array<string, mixed>  $data */
    public function sync(array $data): TaxChargeSetting
    {
        $settings = TaxChargeSetting::current();
        $settings->update($data);
        $this->clearCache();

        return $settings->fresh();
    }
}
