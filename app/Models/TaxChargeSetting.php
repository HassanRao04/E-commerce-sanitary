<?php

namespace App\Models;

use App\Enums\ChargeCalculationType;
use App\Enums\TaxType;
use Illuminate\Database\Eloquent\Model;

class TaxChargeSetting extends Model
{
    protected $fillable = [
        'vat_enabled',
        'vat_rate',
        'gst_enabled',
        'gst_rate',
        'sales_tax_enabled',
        'sales_tax_rate',
        'default_tax_type',
        'service_charge_enabled',
        'service_charge_type',
        'service_charge_value',
        'handling_charge_enabled',
        'handling_charge_type',
        'handling_charge_value',
    ];

    protected function casts(): array
    {
        return [
            'vat_enabled' => 'boolean',
            'vat_rate' => 'decimal:2',
            'gst_enabled' => 'boolean',
            'gst_rate' => 'decimal:2',
            'sales_tax_enabled' => 'boolean',
            'sales_tax_rate' => 'decimal:2',
            'default_tax_type' => TaxType::class,
            'service_charge_enabled' => 'boolean',
            'service_charge_type' => ChargeCalculationType::class,
            'service_charge_value' => 'decimal:2',
            'handling_charge_enabled' => 'boolean',
            'handling_charge_type' => ChargeCalculationType::class,
            'handling_charge_value' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'vat_enabled' => false,
            'vat_rate' => 0,
            'gst_enabled' => true,
            'gst_rate' => 0,
            'sales_tax_enabled' => false,
            'sales_tax_rate' => 0,
            'default_tax_type' => TaxType::Gst,
            'service_charge_enabled' => false,
            'service_charge_type' => ChargeCalculationType::Percent,
            'service_charge_value' => 0,
            'handling_charge_enabled' => false,
            'handling_charge_type' => ChargeCalculationType::Fixed,
            'handling_charge_value' => 0,
        ]);
    }
}
