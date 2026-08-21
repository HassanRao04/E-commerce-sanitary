<?php

namespace Tests\Feature\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\ProductVariationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class ProductVariationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_placeholder_paths_are_deterministic_and_not_random(): void
    {
        $this->seed(AttributeSeeder::class);

        $brand = Brand::factory()->create();
        $category = Category::query()->create([
            'name' => 'Bathroom Faucets',
            'slug' => 'bathroom-faucets-seeder-test',
            'is_active' => true,
        ]);
        $product = Product::factory()->for($brand)->create([
            'product_type' => 'simple',
            'base_sku' => 'VAR-SEED-001',
        ]);
        $product->categories()->attach($category->id);
        $product->defaultVariant?->update(['sku' => 'VAR-SEED-001']);

        app(ProductVariationSeeder::class)->run();

        $product->refresh()->load('variants.images');

        $this->assertSame('variable', $product->product_type);
        $this->assertTrue($product->variants->isNotEmpty());

        foreach ($product->variants as $variant) {
            if ($variant->images->isEmpty()) {
                continue;
            }

            $path = $variant->images->first()->image_path;
            $this->assertSame("products/{$product->id}/variants/{$variant->id}.svg", $path);
            $this->assertDoesNotMatchRegularExpression('/variants\/[a-z0-9]{20,}\.svg/', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_existing_variant_placeholder_files_are_reused_without_rewriting(): void
    {
        $path = 'products/99/variants/5.svg';
        $originalContents = '<svg xmlns="http://www.w3.org/2000/svg"><text>existing variant</text></svg>';
        Storage::disk('public')->put($path, $originalContents);

        $method = new ReflectionMethod(ProductVariationSeeder::class, 'ensureVariantPlaceholderFile');
        $method->setAccessible(true);
        $method->invoke(app(ProductVariationSeeder::class), $path, '#C0C0C0', 'Chrome');

        $this->assertSame($originalContents, Storage::disk('public')->get($path));
    }

    public function test_deleting_variant_image_rows_and_re_attaching_does_not_increase_file_count(): void
    {
        $this->seed(AttributeSeeder::class);

        $brand = Brand::factory()->create();
        $category = Category::query()->create([
            'name' => 'Kitchen Faucets',
            'slug' => 'kitchen-faucets-seeder-test',
            'is_active' => true,
        ]);
        $product = Product::factory()->for($brand)->create([
            'product_type' => 'simple',
            'base_sku' => 'VAR-SEED-002',
        ]);
        $product->categories()->attach($category->id);
        $product->defaultVariant?->update(['sku' => 'VAR-SEED-002']);

        $seeder = app(ProductVariationSeeder::class);
        $seeder->run();

        $directory = "products/{$product->id}";
        $filesAfterFirstRun = count(Storage::disk('public')->allFiles($directory));

        ProductImage::query()
            ->where('product_id', $product->id)
            ->whereNotNull('product_variant_id')
            ->delete();

        $attach = new ReflectionMethod(ProductVariationSeeder::class, 'attachVariantImages');
        $attach->setAccessible(true);
        $attach->invoke($seeder, $product->fresh(['variants.images']));

        $filesAfterSecondAttach = count(Storage::disk('public')->allFiles($directory));

        $this->assertSame($filesAfterFirstRun, $filesAfterSecondAttach);
        $this->assertGreaterThan(0, $product->fresh()->variants->flatMap->images->count());
    }
}
