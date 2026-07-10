<?php

namespace Tests\Feature\Admin;

use App\Models\CheckoutRulesSetting;
use App\Models\User;
use App\Services\CheckoutRulesSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutRulesSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_checkout_rules_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.checkout.rules.edit'))
            ->assertOk()
            ->assertSee('Checkout Rules')
            ->assertSee('Checkout formula')
            ->assertSee('Shipping rules')
            ->assertSee('Tax rules');
    }

    public function test_admin_can_update_checkout_rules(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.checkout.rules.update'), [
                'minimum_order_enabled' => '1',
                'minimum_order_amount' => 2500,
                'coupons_enabled' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        app(CheckoutRulesSettingsService::class)->clearCache();

        $settings = CheckoutRulesSetting::current()->fresh();
        $this->assertTrue($settings->minimum_order_enabled);
        $this->assertEquals(2500.0, (float) $settings->minimum_order_amount);
        $this->assertFalse($settings->coupons_enabled);
    }

    public function test_guest_cannot_access_checkout_rules(): void
    {
        $this->get(route('admin.checkout.rules.edit'))
            ->assertRedirect(route('login'));
    }
}
