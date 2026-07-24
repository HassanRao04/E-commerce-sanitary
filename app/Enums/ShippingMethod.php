<?php

namespace App\Enums;

enum ShippingMethod: string
{
    case Flat = 'flat';
    case Product = 'product';
    case Category = 'category';

    /**
     * Add new methods here, then:
     * 1) Map enabledSettingKey() to a ShippingSetting boolean column
     * 2) Register a calculator in ShippingCalculatorService::methodCalculators()
     * 3) Expose the option in admin shipping settings
     */
    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Flat rate',
            self::Product => 'Product-based',
            self::Category => 'Category-based',
        };
    }

    /**
     * ShippingSetting column that marks this method as the active default.
     * Return null for methods that are not yet wired to settings flags.
     */
    public function enabledSettingKey(): ?string
    {
        return match ($this) {
            self::Flat => 'flat_rate_enabled',
            self::Product => 'product_rate_enabled',
            self::Category => 'category_rate_enabled',
        };
    }
}
