<?php

namespace Tests\Feature\Admin;

use App\Enums\ChargeCalculationType;
use App\Enums\TaxType;
use App\Models\TaxChargeSetting;
use App\Models\User;
use App\Services\TaxChargeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxChargeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_tax_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tax.settings.edit'))
            ->assertOk()
            ->assertSee('Tax & Charges')
            ->assertSee('GST')
            ->assertSee('Service charge');
    }

    public function test_admin_can_update_tax_and_charge_settings(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.tax.settings.update'), [
                'vat_enabled' => '1',
                'vat_rate' => 5,
                'gst_enabled' => '1',
                'gst_rate' => 17,
                'sales_tax_enabled' => '0',
                'sales_tax_rate' => 0,
                'default_tax_type' => TaxType::Gst->value,
                'service_charge_enabled' => '1',
                'service_charge_type' => ChargeCalculationType::Percent->value,
                'service_charge_value' => 2.5,
                'handling_charge_enabled' => '1',
                'handling_charge_type' => ChargeCalculationType::Fixed->value,
                'handling_charge_value' => 50,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        app(TaxChargeSettingsService::class)->clearCache();

        $settings = TaxChargeSetting::current()->fresh();
        $this->assertTrue($settings->gst_enabled);
        $this->assertEquals(17.0, (float) $settings->gst_rate);
        $this->assertEquals(TaxType::Gst, $settings->default_tax_type);
        $this->assertTrue($settings->service_charge_enabled);
        $this->assertEquals(50.0, (float) $settings->handling_charge_value);
    }

    public function test_default_tax_type_must_be_enabled(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.tax.settings.edit'))
            ->patch(route('admin.tax.settings.update'), [
                'vat_enabled' => '0',
                'vat_rate' => 5,
                'gst_enabled' => '0',
                'gst_rate' => 17,
                'sales_tax_enabled' => '0',
                'sales_tax_rate' => 0,
                'default_tax_type' => TaxType::Gst->value,
                'service_charge_enabled' => '0',
                'service_charge_type' => ChargeCalculationType::Percent->value,
                'service_charge_value' => 0,
                'handling_charge_enabled' => '0',
                'handling_charge_type' => ChargeCalculationType::Fixed->value,
                'handling_charge_value' => 0,
            ])
            ->assertRedirect(route('admin.tax.settings.edit'))
            ->assertSessionHasErrors('default_tax_type');
    }
}
