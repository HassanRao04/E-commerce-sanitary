<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPipeLengthConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_save_pipe_length_options(): void
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
            'pipe_length_enabled' => '1',
            'option_title' => 'Hose Length',
            'pipe_length_options' => [
                ['label' => '1 Meter', 'additional_price' => 0],
                ['label' => '1.5 Meter', 'additional_price' => 250],
                ['label' => '2 Meter', 'additional_price' => 500],
                ['label' => '2.5 Meter', 'additional_price' => 900],
                ['label' => '3 Meter', 'additional_price' => 1200],
            ],
            'category_ids' => $product->categories()->pluck('categories.id')->all(),
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertTrue($product->pipe_length_enabled);
        $this->assertSame('Hose Length', $product->option_title);
        $this->assertSame('Hose Length', $product->resolvedOptionTitle());
        $this->assertCount(5, $product->pipeLengthOptions);

        $this->assertDatabaseHas('product_pipe_length_options', [
            'product_id' => $product->id,
            'label' => '1.5 Meter',
            'additional_price' => 250,
        ]);
        $this->assertDatabaseHas('product_pipe_length_options', [
            'product_id' => $product->id,
            'label' => '3 Meter',
            'additional_price' => 1200,
        ]);
    }

    public function test_disabled_pipe_length_does_not_affect_other_products(): void
    {
        $products = Product::query()->active()->with('defaultVariant')->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $products->count());

        $enabled = $products[0];
        $untouched = $products[1];

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $enabled), [
                'brand_id' => $enabled->brand_id,
                'name' => $enabled->name,
                'slug' => $enabled->slug,
                'base_sku' => $enabled->base_sku,
                'status' => $enabled->status->value,
                'product_type' => 'simple',
                'price' => (float) ($enabled->defaultVariant?->price ?? 100),
                'stock_quantity' => (int) ($enabled->defaultVariant?->stock_quantity ?? 10),
                'pipe_length_enabled' => '1',
                'option_title' => 'Size',
                'pipe_length_options' => [
                    ['label' => '2 Meter', 'additional_price' => 500],
                ],
                'category_ids' => $enabled->categories()->pluck('categories.id')->all(),
            ])
            ->assertRedirect();

        $this->assertTrue($enabled->fresh()->pipe_length_enabled);
        $this->assertSame('Size', $enabled->fresh()->option_title);
        $this->assertFalse($untouched->fresh()->pipe_length_enabled);
        $this->assertCount(0, $untouched->fresh()->pipeLengthOptions);
    }
}
