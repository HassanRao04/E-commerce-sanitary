<?php

namespace Database\Seeders;

use App\Enums\ChargeCalculationType;
use App\Enums\TaxType;
use App\Models\TaxChargeSetting;
use App\Services\TaxChargeSettingsService;
use Illuminate\Database\Seeder;

class TaxChargeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        TaxChargeSetting::current()->update([
            'vat_enabled' => false,
            'vat_rate' => 0,
            'gst_enabled' => true,
            'gst_rate' => 17,
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

        app(TaxChargeSettingsService::class)->clearCache();
    }
}
