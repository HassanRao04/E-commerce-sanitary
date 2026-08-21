<?php

namespace Tests\Feature\Console;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\Storage\ProductImagePathNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupOrphanProductSvgsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_referenced_svg_is_never_deleted(): void
    {
        $path = 'products/1/gallery-1.svg';
        Storage::disk('public')->put($path, '<svg/>');
        Storage::disk('public')->put('products/1/gallery-1-abcdef12.svg', '<svg/>');

        $product = Product::factory()->create();
        ProductImage::query()->create([
            'product_id' => $product->id,
            'image_path' => $path,
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('products/1/gallery-1-abcdef12.svg');
    }

    public function test_random_orphan_svg_is_deleted_with_execute(): void
    {
        Storage::disk('public')->put('products/5/gallery-2-deadbeef.svg', '<svg/>');
        Storage::disk('public')->put('products/5/variants/abcdefghijklmnopqrst.svg', '<svg/>');

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertMissing('products/5/gallery-2-deadbeef.svg');
        Storage::disk('public')->assertMissing('products/5/variants/abcdefghijklmnopqrst.svg');
    }

    public function test_dry_run_deletes_nothing(): void
    {
        Storage::disk('public')->put('products/9/gallery-1-12345678.svg', '<svg/>');

        Artisan::call('storage:cleanup-orphan-product-svgs');
        Artisan::call('storage:cleanup-orphan-product-svgs', ['--dry-run' => true]);

        Storage::disk('public')->assertExists('products/9/gallery-1-12345678.svg');
    }

    public function test_non_svg_orphan_is_never_deleted(): void
    {
        Storage::disk('public')->put('products/2/photo.jpg', 'jpg');
        Storage::disk('public')->put('products/2/gallery-1-12345678.svg', '<svg/>');

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertExists('products/2/photo.jpg');
        Storage::disk('public')->assertMissing('products/2/gallery-1-12345678.svg');
    }

    public function test_deterministic_svg_referenced_by_db_is_preserved(): void
    {
        $path = 'products/3/variants/42.svg';
        Storage::disk('public')->put($path, '<svg/>');
        Storage::disk('public')->put('products/3/variants/zzzzzzzzzzzzzzzzzzzzzzzz.svg', '<svg/>');

        $product = Product::factory()->create();
        ProductImage::query()->create([
            'product_id' => $product->id,
            'image_path' => $path,
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('products/3/variants/zzzzzzzzzzzzzzzzzzzzzzzz.svg');
    }

    public function test_unreferenced_deterministic_svg_is_not_deleted(): void
    {
        Storage::disk('public')->put('products/4/gallery-2.svg', '<svg/>');
        Storage::disk('public')->put('products/4/gallery-2-12345678.svg', '<svg/>');

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertExists('products/4/gallery-2.svg');
        Storage::disk('public')->assertMissing('products/4/gallery-2-12345678.svg');
    }

    public function test_path_traversal_candidate_outside_products_is_not_deleted(): void
    {
        Storage::disk('public')->put('outside.svg', '<svg/>');
        Storage::disk('public')->put('products/7/gallery-1-12345678.svg', '<svg/>');

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertExists('outside.svg');
        Storage::disk('public')->assertMissing('products/7/gallery-1-12345678.svg');
    }

    public function test_database_paths_are_normalized_correctly(): void
    {
        $this->assertSame('products/1/gallery-1.svg', ProductImagePathNormalizer::normalize('/products/1/gallery-1.svg'));
        $this->assertSame('products/1/gallery-1.svg', ProductImagePathNormalizer::normalize('storage/app/public/products/1/gallery-1.svg'));
        $this->assertSame('products/1/gallery-1.svg', ProductImagePathNormalizer::normalize('/storage/products/1/gallery-1.svg'));
        $this->assertNull(ProductImagePathNormalizer::normalize('https://cdn.example.com/products/1/gallery-1.svg'));
        $this->assertNull(ProductImagePathNormalizer::normalize('products/../secrets.svg'));

        Storage::disk('public')->put('products/10/gallery-1.svg', '<svg/>');

        $product = Product::factory()->create();
        ProductImage::query()->create([
            'product_id' => $product->id,
            'image_path' => '/storage/products/10/gallery-1.svg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put('products/10/gallery-1-12345678.svg', '<svg/>');

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertExists('products/10/gallery-1.svg');
        Storage::disk('public')->assertMissing('products/10/gallery-1-12345678.svg');
    }

    public function test_re_running_cleanup_is_idempotent(): void
    {
        Storage::disk('public')->put('products/11/gallery-1-12345678.svg', '<svg/>');

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);
        Storage::disk('public')->assertMissing('products/11/gallery-1-12345678.svg');

        $exitCode = Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        $this->assertSame(0, $exitCode);
        Storage::disk('public')->assertMissing('products/11/gallery-1-12345678.svg');
    }

    public function test_failed_deletion_is_logged_without_deleting_unrelated_files(): void
    {
        Storage::disk('public')->put('products/12/gallery-1-12345678.svg', '<svg/>');
        Storage::disk('public')->put('products/12/gallery-2-12345678.svg', '<svg/>');
        Storage::disk('public')->put('products/12/keep-me.svg', '<svg/>');
        Storage::disk('public')->put('products/12/photo.jpg', 'binary');

        Artisan::call('storage:cleanup-orphan-product-svgs', ['--execute' => true]);

        Storage::disk('public')->assertMissing('products/12/gallery-1-12345678.svg');
        Storage::disk('public')->assertMissing('products/12/gallery-2-12345678.svg');
        Storage::disk('public')->assertExists('products/12/keep-me.svg');
        Storage::disk('public')->assertExists('products/12/photo.jpg');

        $reportDirs = glob(storage_path('app/private/orphan-cleanup-*'));
        $latestReport = end($reportDirs);
        $this->assertNotFalse($latestReport);
        $this->assertFileExists($latestReport.'/summary.json');
        $this->assertFileExists($latestReport.'/deleted-files.txt');
        $this->assertFileExists($latestReport.'/failed-files.txt');
    }

    public function test_default_command_output_is_dry_run(): void
    {
        Storage::disk('public')->put('products/13/gallery-1-12345678.svg', '<svg/>');

        $exitCode = Artisan::call('storage:cleanup-orphan-product-svgs');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Dry-run', $output);
        $this->assertStringContainsString('No files were deleted', $output);
        Storage::disk('public')->assertExists('products/13/gallery-1-12345678.svg');
    }
}
