<?php

namespace Tests\Unit;

use App\Enums\ChargeCalculationType;
use App\Enums\TaxType;
use App\Models\TaxChargeSetting;
use App\Services\TaxChargeCalculatorService;
use App\Services\TaxChargeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxChargeCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_gst_is_calculated_on_taxable_base(): void
    {
        $this->configure([
            'gst_enabled' => true,
            'gst_rate' => 17,
            'default_tax_type' => TaxType::Gst,
            'service_charge_enabled' => false,
            'handling_charge_enabled' => false,
        ]);

        $result = app(TaxChargeCalculatorService::class)->calculate(1000.0, 100.0);

        $this->assertEquals(187.0, $result['tax']);
        $this->assertSame('gst', $result['tax_type']);
        $this->assertSame('GST', $result['tax_label']);
    }

    public function test_service_and_handling_charges_are_included_before_tax(): void
    {
        $this->configure([
            'gst_enabled' => true,
            'gst_rate' => 10,
            'default_tax_type' => TaxType::Gst,
            'service_charge_enabled' => true,
            'service_charge_type' => ChargeCalculationType::Percent,
            'service_charge_value' => 5,
            'handling_charge_enabled' => true,
            'handling_charge_type' => ChargeCalculationType::Fixed,
            'handling_charge_value' => 50,
        ]);

        $result = app(TaxChargeCalculatorService::class)->calculate(1000.0, 0.0);

        $this->assertEquals(50.0, $result['service_charge']);
        $this->assertEquals(50.0, $result['handling_charge']);
        $this->assertEquals(110.0, $result['tax']);
    }

    public function test_no_tax_when_default_tax_type_is_none(): void
    {
        $this->configure([
            'gst_enabled' => true,
            'gst_rate' => 17,
            'default_tax_type' => TaxType::None,
        ]);

        $result = app(TaxChargeCalculatorService::class)->calculate(1000.0, 0.0);

        $this->assertEquals(0.0, $result['tax']);
        $this->assertNull($result['tax_type']);
    }

    /** @param  array<string, mixed>  $attributes */
    private function configure(array $attributes): void
    {
        TaxChargeSetting::current()->update($attributes);
        app(TaxChargeSettingsService::class)->clearCache();
    }
}
