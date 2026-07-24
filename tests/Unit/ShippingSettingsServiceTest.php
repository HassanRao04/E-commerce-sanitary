<?php

namespace Tests\Unit;

use App\Enums\ShippingMethod;
use App\Models\ShippingSetting;
use App\Services\ShippingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_default_method_always_matches_saved_selection(): void
    {
        ShippingSetting::current()->update([
            'default_method' => ShippingMethod::Product,
            'flat_rate_enabled' => true,
            'product_rate_enabled' => false,
            'category_rate_enabled' => false,
        ]);

        app(ShippingSettingsService::class)->clearCache();

        $this->assertEquals(
            ShippingMethod::Product,
            app(ShippingSettingsService::class)->defaultMethod(),
        );
    }

    public function test_sync_enables_only_the_selected_default_method(): void
    {
        $settings = app(ShippingSettingsService::class)->sync(
            [
                'flat_rate_amount' => 200,
                'flat_rate_enabled' => true,
                'product_rate_enabled' => true,
                'category_rate_enabled' => true,
                'free_shipping_enabled' => true,
                'free_shipping_threshold' => 8000,
                'default_method' => ShippingMethod::Category,
            ],
            [],
            [],
        );

        $this->assertEquals(ShippingMethod::Category, $settings->default_method);
        $this->assertFalse($settings->flat_rate_enabled);
        $this->assertFalse($settings->product_rate_enabled);
        $this->assertTrue($settings->category_rate_enabled);
        $this->assertEquals(
            ShippingMethod::Category,
            app(ShippingSettingsService::class)->defaultMethod(),
        );
    }
}
