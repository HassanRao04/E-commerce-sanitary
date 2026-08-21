<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\AttributeValue;
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

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required for product image tests.');
        }
    }

    private function fakeProductImage(string $name, int $width = 2000, int $height = 1500): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
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
                $this->fakeProductImage('basin.jpg'),
                $this->fakeProductImage('basin-2.png'),
            ],
        ]);

        $product = Product::where('slug', 'premium-basin')->first();
        $response->assertRedirect(route('admin.products.edit', $product));

        $this->assertDatabaseHas('products', ['base_sku' => 'PB-001', 'product_type' => 'simple']);
        $this->assertEquals(2, $product->images()->count());
        $this->assertEquals(1, $product->attributeValues()->count());
        $this->assertEquals(15, $product->defaultVariant->stock_quantity);

        foreach ($product->images as $image) {
            $this->assertStringEndsWith('.webp', $image->image_path);
            Storage::disk('public')->assertExists($image->image_path);

            $storedPath = Storage::disk('public')->path($image->image_path);
            $dimensions = getimagesize($storedPath);
            $this->assertIsArray($dimensions);
            $this->assertLessThanOrEqual(1600, max($dimensions[0], $dimensions[1]));
        }
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

    public function test_admin_can_create_variable_product_with_variation_builder(): void
    {
        $brand = Brand::first();

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Basin Series',
            'slug' => 'basin-series',
            'base_sku' => 'BS-BASE',
            'brand_id' => $brand->id,
            'status' => 'active',
            'product_type' => 'variable',
            'variants' => [
                [
                    'sku' => 'BS-BASE-BLACK-24-INCH',
                    'variant_name' => 'Black / 24 Inch',
                    'price' => 12000,
                    'sale_price' => 10999,
                    'stock_quantity' => 4,
                    'is_default' => 1,
                    'is_active' => 1,
                    'attribute_values' => [
                        [
                            'attribute_name' => 'Color',
                            'attribute_slug' => 'color',
                            'value' => 'Black',
                            'color_hex' => '#000000',
                        ],
                        [
                            'attribute_name' => 'Size',
                            'attribute_slug' => 'size',
                            'value' => '24 Inch',
                        ],
                    ],
                ],
                [
                    'sku' => 'BS-BASE-WHITE-24-INCH',
                    'variant_name' => 'White / 24 Inch',
                    'price' => 12000,
                    'stock_quantity' => 6,
                    'is_default' => 0,
                    'is_active' => 1,
                    'attribute_values' => [
                        [
                            'attribute_name' => 'Color',
                            'attribute_slug' => 'color',
                            'value' => 'White',
                            'color_hex' => '#FFFFFF',
                        ],
                        [
                            'attribute_name' => 'Size',
                            'attribute_slug' => 'size',
                            'value' => '24 Inch',
                        ],
                    ],
                ],
            ],
        ]);

        $product = Product::where('slug', 'basin-series')->first();
        $response->assertRedirect(route('admin.products.edit', $product));

        $this->assertEquals('variable', $product->product_type);
        $this->assertEquals(2, $product->variants()->count());

        $colorAttribute = Attribute::where('slug', 'color')->first();
        $this->assertNotNull($colorAttribute);
        $this->assertSame('color', $colorAttribute->type);
        $this->assertTrue($colorAttribute->is_variant_attribute);

        $blackValue = AttributeValue::where('attribute_id', $colorAttribute->id)
            ->where('slug', 'black')
            ->first();
        $this->assertSame('#000000', $blackValue->color_hex);

        $variant = $product->variants()->where('sku', 'BS-BASE-BLACK-24-INCH')->first();
        $this->assertNotNull($variant);
        $this->assertEquals('Black', $variant->color_name);
        $this->assertSame('#000000', $variant->color_hex);
        $this->assertEquals('24 Inch', $variant->size);
        $this->assertEquals(2, $variant->attributeValues()->count());
        $this->assertEquals(10999, (float) $variant->sale_price);

        $swatch = $variant->swatchColor();
        $this->assertSame('Black', $swatch['name']);
        $this->assertSame('#000000', $swatch['hex']);
    }

    public function test_admin_can_upload_variant_image_on_update(): void
    {
        $product = Product::where('product_type', 'variable')->first()
            ?? Product::factory()->create(['product_type' => 'variable']);

        $variant = $product->variants()->first();
        if (! $variant) {
            $variant = $product->variants()->create([
                'sku' => 'TEST-V1',
                'variant_name' => 'Test',
                'price' => 1000,
                'stock_quantity' => 5,
                'is_default' => true,
                'is_active' => true,
            ]);
            $product->update(['default_variant_id' => $variant->id]);
        }

        $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'base_sku' => $product->base_sku,
            'status' => $product->status->value,
            'product_type' => 'variable',
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'variant_name' => $variant->variant_name,
                    'price' => $variant->price,
                    'stock_quantity' => $variant->stock_quantity,
                    'is_default' => 1,
                    'is_active' => 1,
                    'attribute_values' => [[
                        'attribute_name' => 'Color',
                        'attribute_slug' => 'color',
                        'value' => 'Black',
                    ]],
                    'image' => $this->fakeProductImage('variant.jpg'),
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.edit', $product));
        $variant->refresh();
        $this->assertEquals(1, $variant->images()->count());

        $variantImage = $variant->images()->first();
        $this->assertStringEndsWith("variants/{$variant->id}.webp", $variantImage->image_path);
        Storage::disk('public')->assertExists($variantImage->image_path);
    }

    public function test_replacing_variant_image_does_not_leave_duplicate_files(): void
    {
        $product = Product::where('product_type', 'variable')->first()
            ?? Product::factory()->create(['product_type' => 'variable']);

        $variant = $product->variants()->first();
        if (! $variant) {
            $variant = $product->variants()->create([
                'sku' => 'TEST-V2',
                'variant_name' => 'Test Replace',
                'price' => 1000,
                'stock_quantity' => 5,
                'is_default' => true,
                'is_active' => true,
            ]);
            $product->update(['default_variant_id' => $variant->id]);
        }

        $payload = [
            'name' => $product->name,
            'slug' => $product->slug,
            'base_sku' => $product->base_sku,
            'status' => $product->status->value,
            'product_type' => 'variable',
            'variants' => [[
                'id' => $variant->id,
                'sku' => $variant->sku,
                'variant_name' => $variant->variant_name,
                'price' => $variant->price,
                'stock_quantity' => $variant->stock_quantity,
                'is_default' => 1,
                'is_active' => 1,
                'attribute_values' => [[
                    'attribute_name' => 'Color',
                    'attribute_slug' => 'color',
                    'value' => 'Black',
                ]],
            ]],
        ];

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), array_merge($payload, [
            'variants' => [[
                ...$payload['variants'][0],
                'image' => $this->fakeProductImage('variant-first.jpg'),
            ]],
        ]))->assertRedirect();

        $firstPath = $variant->fresh()->images()->first()->image_path;

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), array_merge($payload, [
            'variants' => [[
                ...$payload['variants'][0],
                'image' => $this->fakeProductImage('variant-second.jpg', 1200, 900),
            ]],
        ]))->assertRedirect();

        $secondPath = $variant->fresh()->images()->first()->image_path;

        $this->assertSame($firstPath, $secondPath);
        $this->assertCount(1, Storage::disk('public')->allFiles("products/{$product->id}/variants"));
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
