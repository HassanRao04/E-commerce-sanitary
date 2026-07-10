<?php

namespace App\Services;

use App\Enums\ChargeCalculationType;

class TaxChargeCalculatorService
{
    public function __construct(
        private readonly TaxChargeSettingsService $settings,
    ) {}

    /**
     * @return array{
     *     service_charge: float,
     *     handling_charge: float,
     *     tax: float,
     *     tax_rate: float,
     *     tax_type: string|null,
     *     tax_label: string,
     *     service_charge_type: string|null,
     *     service_charge_value: float,
     *     handling_charge_type: string|null,
     *     handling_charge_value: float
     * }
     */
    public function calculate(float $discountedSubtotal, float $shipping): array
    {
        $settings = $this->settings->settings();

        $serviceCharge = $this->calculateCharge(
            $discountedSubtotal,
            (bool) $settings->service_charge_enabled,
            $settings->service_charge_type ?? ChargeCalculationType::Percent,
            (float) $settings->service_charge_value,
        );

        $handlingCharge = $this->calculateCharge(
            $discountedSubtotal,
            (bool) $settings->handling_charge_enabled,
            $settings->handling_charge_type ?? ChargeCalculationType::Fixed,
            (float) $settings->handling_charge_value,
        );

        $taxRate = $this->settings->activeTaxRate();
        $taxType = $this->settings->activeTaxType();
        $taxLabel = $this->settings->activeTaxLabel();

        $taxBase = $discountedSubtotal + $shipping + $serviceCharge + $handlingCharge;
        $tax = round($taxBase * ($taxRate / 100), 2);

        return [
            'service_charge' => round($serviceCharge, 2),
            'handling_charge' => round($handlingCharge, 2),
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'tax_type' => $taxType?->value,
            'tax_label' => $taxLabel,
            'service_charge_type' => $settings->service_charge_enabled
                ? ($settings->service_charge_type?->value ?? ChargeCalculationType::Percent->value)
                : null,
            'service_charge_value' => $settings->service_charge_enabled
                ? (float) $settings->service_charge_value
                : 0.0,
            'handling_charge_type' => $settings->handling_charge_enabled
                ? ($settings->handling_charge_type?->value ?? ChargeCalculationType::Fixed->value)
                : null,
            'handling_charge_value' => $settings->handling_charge_enabled
                ? (float) $settings->handling_charge_value
                : 0.0,
        ];
    }

    private function calculateCharge(
        float $base,
        bool $enabled,
        ChargeCalculationType $type,
        float $value,
    ): float {
        if (! $enabled || $value <= 0) {
            return 0.0;
        }

        return match ($type) {
            ChargeCalculationType::Percent => $base * ($value / 100),
            ChargeCalculationType::Fixed => $value,
        };
    }
}
