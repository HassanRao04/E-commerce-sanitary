<?php

namespace Tests\Feature\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\ProductImageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_seeder_twice_does_not_increase_placeholder_file_count(): void
    {
        $product = Product::factory()->create();
        $targetCount = 3 + ($product->id % 3);
        $seeder = new ProductImageSeeder;

        $seeder->run();

        $directory = "products/{$product->id}";
        $filesAfterFirstRun = Storage::disk('public')->allFiles($directory);
        $pathsAfterFirstRun = $product->fresh()->images()->orderBy('sort_order')->pluck('image_path');

        $this->assertCount($targetCount, $filesAfterFirstRun);
        $this->assertCount($targetCount, $pathsAfterFirstRun);

        ProductImage::query()->where('product_id', $product->id)->delete();

        $seeder->run();

        $filesAfterSecondRun = Storage::disk('public')->allFiles($directory);
        $pathsAfterSecondRun = $product->fresh()->images()->orderBy('sort_order')->pluck('image_path');

        $this->assertCount($targetCount, $filesAfterSecondRun);
        $this->assertSame($pathsAfterFirstRun->all(), $pathsAfterSecondRun->all());
    }

    public function test_placeholder_paths_are_deterministic_and_not_random(): void
    {
        $product = Product::factory()->create();
        $targetCount = 3 + ($product->id % 3);

        (new ProductImageSeeder)->run();

        $paths = $product->fresh()->images()->orderBy('sort_order')->pluck('image_path');

        $this->assertCount($targetCount, $paths);

        foreach ($paths as $index => $path) {
            $slot = $index + 1;
            $this->assertSame("products/{$product->id}/gallery-{$slot}.svg", $path);
            $this->assertDoesNotMatchRegularExpression('/gallery-\d+-[a-z0-9]+\.svg/', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_existing_placeholder_files_are_reused_without_rewriting(): void
    {
        $product = Product::factory()->create();
        $path = "products/{$product->id}/gallery-1.svg";
        $originalContents = '<svg xmlns="http://www.w3.org/2000/svg"><text>existing</text></svg>';

        Storage::disk('public')->put($path, $originalContents);

        (new ProductImageSeeder)->run();

        $this->assertSame($originalContents, Storage::disk('public')->get($path));
    }

    public function test_running_seeder_twice_on_same_database_is_a_no_op_for_files(): void
    {
        $product = Product::factory()->create();
        $seeder = new ProductImageSeeder;

        $seeder->run();
        $firstCount = count(Storage::disk('public')->allFiles("products/{$product->id}"));

        $seeder->run();
        $secondCount = count(Storage::disk('public')->allFiles("products/{$product->id}"));

        $this->assertSame($firstCount, $secondCount);
    }
}
