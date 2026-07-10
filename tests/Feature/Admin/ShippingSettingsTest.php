<?php

namespace Tests\Feature\Admin;

use App\Enums\ShippingMethod;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Services\ShippingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_shipping_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.shipping.settings.edit'))
            ->assertOk()
            ->assertSee('Shipping Settings')
            ->assertSee('Flat rate shipping')
            ->assertSee('Free shipping above');
    }

    public function test_admin_can_update_shipping_settings(): void
    {
        $product = Product::query()->first();
        $category = Category::query()->active()->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.shipping.settings.update'), [
                'flat_rate_enabled' => '1',
                'flat_rate_amount' => 150,
                'product_rate_enabled' => '1',
                'category_rate_enabled' => '1',
                'free_shipping_enabled' => '1',
                'free_shipping_threshold' => 6000,
                'default_method' => ShippingMethod::Flat->value,
                'product_rates' => [
                    [
                        'product_id' => $product->id,
                        'amount' => 275,
                        'is_active' => '1',
                    ],
                ],
                'category_rates' => [
                    [
                        'category_id' => $category->id,
                        'amount' => 225,
                        'is_active' => '1',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        app(ShippingSettingsService::class)->clearCache();

        $settings = ShippingSetting::current()->fresh();
        $this->assertTrue($settings->flat_rate_enabled);
        $this->assertEquals(150.0, (float) $settings->flat_rate_amount);
        $this->assertEquals(6000.0, (float) $settings->free_shipping_threshold);
        $this->assertEquals(ShippingMethod::Flat, $settings->default_method);

        $this->assertDatabaseHas('product_shipping_rates', [
            'product_id' => $product->id,
            'amount' => 275,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('category_shipping_rates', [
            'category_id' => $category->id,
            'amount' => 225,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_search_products_for_shipping_rates(): void
    {
        $product = Product::query()->first();

        $this->actingAs($this->admin)
            ->getJson(route('admin.shipping.settings.products.search', ['q' => $product->name]))
            ->assertOk()
            ->assertJsonFragment(['id' => $product->id]);
    }
}
