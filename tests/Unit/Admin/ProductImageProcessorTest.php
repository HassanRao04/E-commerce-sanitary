<?php

namespace Tests\Unit\Admin;

use App\Services\Admin\ProductImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProductImageProcessorTest extends TestCase
{
    private ProductImageProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required for product image processor tests.');
        }

        Storage::fake('public');
        $this->processor = new ProductImageProcessor;
    }

    public function test_it_converts_uploads_to_webp_and_limits_dimensions(): void
    {
        config(['media.product.max_dimension' => 1600]);

        $upload = UploadedFile::fake()->image('large.jpg', 2400, 1800);

        $path = $this->processor->storeOptimized($upload, 'products/99', 'gallery.webp');

        $this->assertSame('products/99/gallery.webp', $path);
        Storage::disk('public')->assertExists($path);

        $storedPath = Storage::disk('public')->path($path);
        $this->assertSame('webp', strtolower(pathinfo($storedPath, PATHINFO_EXTENSION)));

        $imageInfo = getimagesize($storedPath);
        $this->assertIsArray($imageInfo);
        $this->assertSame(1600, $imageInfo[0]);
        $this->assertSame(1200, $imageInfo[1]);
        $this->assertLessThan($upload->getSize(), filesize($storedPath));
    }

    public function test_it_overwrites_variant_files_with_deterministic_name(): void
    {
        config(['media.product.max_dimension' => 800]);

        $first = UploadedFile::fake()->image('variant-a.jpg', 1200, 900);
        $second = UploadedFile::fake()->image('variant-b.jpg', 600, 600);

        $firstPath = $this->processor->storeOptimized($first, 'products/10/variants', '42.webp');
        $firstSize = Storage::disk('public')->size($firstPath);

        $secondPath = $this->processor->storeOptimized($second, 'products/10/variants', '42.webp');
        $secondSize = Storage::disk('public')->size($secondPath);

        $this->assertSame($firstPath, $secondPath);
        $this->assertNotSame($firstSize, $secondSize);
        $this->assertCount(1, Storage::disk('public')->allFiles('products/10/variants'));
    }

    public function test_it_requires_gd(): void
    {
        if (extension_loaded('gd')) {
            $this->markTestSkipped('GD is available in this environment.');
        }

        $this->expectException(RuntimeException::class);

        (new ProductImageProcessor)->storeOptimized(
            UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg'),
            'products/1',
            'photo.webp',
        );
    }
}
