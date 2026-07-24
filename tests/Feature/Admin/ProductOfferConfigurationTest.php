<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOfferConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_save_product_offer_tiers(): void
    {
        $product = Product::query()->active()->with('defaultVariant')->first();
        $this->assertNotNull($product);

        $payload = [
            'brand_id' => $product->brand_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'base_sku' => $product->base_sku,
            'status' => $product->status->value,
            'product_type' => 'simple',
            'price' => (float) ($product->defaultVariant?->price ?? 100),
            'stock_quantity' => (int) ($product->defaultVariant?->stock_quantity ?? 10),
            'offers_enabled' => '1',
            'offer_tiers' => [
                [
                    'buy_quantity' => 2,
                    'discount_percent' => 10,
                    'free_shipping' => '0',
                ],
                [
                    'buy_quantity' => 3,
                    'discount_percent' => 15,
                    'free_shipping' => '1',
                ],
                [
                    'buy_quantity' => 5,
                    'discount_percent' => 25,
                    'free_shipping' => '1',
                ],
            ],
            'category_ids' => $product->categories()->pluck('categories.id')->all(),
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertTrue($product->offers_enabled);
        $this->assertCount(3, $product->offers);

        $this->assertDatabaseHas('product_offers', [
            'product_id' => $product->id,
            'buy_quantity' => 2,
            'discount_percent' => 10,
            'free_shipping' => false,
        ]);
        $this->assertDatabaseHas('product_offers', [
            'product_id' => $product->id,
            'buy_quantity' => 5,
            'discount_percent' => 25,
            'free_shipping' => true,
        ]);
    }

    public function test_disabling_offers_keeps_product_normal_flag_off(): void
    {
        $product = Product::query()->active()->with('defaultVariant')->first();

        ProductOffer::query()->create([
            'product_id' => $product->id,
            'buy_quantity' => 2,
            'discount_percent' => 10,
            'free_shipping' => false,
            'sort_order' => 0,
        ]);

        $payload = [
            'brand_id' => $product->brand_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'base_sku' => $product->base_sku,
            'status' => $product->status->value,
            'product_type' => 'simple',
            'price' => (float) ($product->defaultVariant?->price ?? 100),
            'stock_quantity' => (int) ($product->defaultVariant?->stock_quantity ?? 10),
            'offers_enabled' => '0',
            'offer_tiers' => [
                [
                    'buy_quantity' => 2,
                    'discount_percent' => 10,
                    'free_shipping' => '0',
                ],
            ],
            'category_ids' => $product->categories()->pluck('categories.id')->all(),
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect();

        $this->assertFalse($product->fresh()->offers_enabled);
        $this->assertCount(1, $product->fresh()->offers);
    }
}
