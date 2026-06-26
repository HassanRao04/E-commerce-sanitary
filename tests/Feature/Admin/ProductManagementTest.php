<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
        Storage::fake('public');
    }

    public function test_admin_can_create_simple_product_with_images_and_attributes(): void
    {
        $brand = Brand::first();
        $category = Category::first();
        $attribute = Attribute::where('is_variant_attribute', false)->first();

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Premium Basin',
            'slug' => 'premium-basin',
            'base_sku' => 'PB-001',
            'brand_id' => $brand->id,
            'status' => 'active',
            'product_type' => 'simple',
            'price' => 7500,
            'sale_price' => 6999,
            'stock_quantity' => 15,
            'category_ids' => [$category->id],
            'product_attributes' => [[
                'attribute_id' => $attribute->id,
                'attribute_value_id' => $attribute->values()->first()->id,
            ]],
            'images' => [
                UploadedFile::fake()->create('basin.jpg', 100, 'image/jpeg'),
                UploadedFile::fake()->create('basin-2.png', 100, 'image/png'),
            ],
        ]);

        $product = Product::where('slug', 'premium-basin')->first();
        $response->assertRedirect(route('admin.products.edit', $product));

        $this->assertDatabaseHas('products', ['base_sku' => 'PB-001', 'product_type' => 'simple']);
        $this->assertEquals(2, $product->images()->count());
        $this->assertEquals(1, $product->attributeValues()->count());
        $this->assertEquals(15, $product->defaultVariant->stock_quantity);
    }

    public function test_admin_can_create_variable_product_with_variants(): void
    {
        $brand = Brand::first();
        $finish = Attribute::where('slug', 'finish')->first();

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Mixer Collection',
            'slug' => 'mixer-collection',
            'base_sku' => 'MC-BASE',
            'brand_id' => $brand->id,
            'status' => 'active',
            'product_type' => 'variable',
            'variants' => [
                [
                    'sku' => 'MC-CHROME',
                    'variant_name' => 'Chrome',
                    'price' => 9000,
                    'stock_quantity' => 8,
                    'is_default' => 1,
                    'is_active' => 1,
                    'attribute_values' => [[
                        'attribute_id' => $finish->id,
                        'attribute_value_id' => $finish->values()->where('slug', 'chrome')->first()->id,
                    ]],
                ],
                [
                    'sku' => 'MC-BLACK',
                    'variant_name' => 'Matte Black',
                    'price' => 9500,
                    'sale_price' => 8999,
                    'stock_quantity' => 5,
                    'is_default' => 0,
                    'is_active' => 1,
                    'attribute_values' => [[
                        'attribute_id' => $finish->id,
                        'attribute_value_id' => $finish->values()->where('slug', 'matte-black')->first()->id,
                    ]],
                ],
            ],
        ]);

        $product = Product::where('slug', 'mixer-collection')->first();
        $response->assertRedirect(route('admin.products.edit', $product));

        $this->assertEquals('variable', $product->product_type);
        $this->assertEquals(2, $product->variants()->count());
        $this->assertDatabaseHas('product_variants', ['sku' => 'MC-BLACK', 'sale_price' => 8999]);
    }

    public function test_admin_can_update_product_and_remove_image(): void
    {
        $product = Product::first();
        $image = $product->images()->create([
            'image_path' => 'products/test.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'base_sku' => $product->base_sku,
            'status' => $product->status->value,
            'product_type' => 'simple',
            'price' => $product->defaultVariant->price,
            'stock_quantity' => 20,
            'remove_image_ids' => [$image->id],
        ]);

        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::first();

        $response = $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
