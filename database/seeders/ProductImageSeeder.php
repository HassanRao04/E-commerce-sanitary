<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('products');

        Product::query()
            ->with('images')
            ->orderBy('id')
            ->each(function (Product $product): void {
                $this->seedGallery($product);
            });
    }

    private function seedGallery(Product $product): void
    {
        $validCount = 0;

        foreach ($product->images as $image) {
            if ($this->imageExists($image)) {
                $validCount++;
                continue;
            }

            // Remove broken local image rows so placeholders can replace them.
            if (! str_starts_with((string) $image->image_path, 'http')) {
                $image->delete();
            } else {
                $validCount++;
            }
        }

        $product->unsetRelation('images');

        $targetCount = 3 + ($product->id % 3); // 3–5 images

        if ($validCount >= $targetCount) {
            $this->ensurePrimary($product);

            return;
        }

        $sortOrder = (int) $product->images()->max('sort_order');
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        for ($i = $validCount; $i < $targetCount; $i++) {
            $sortOrder++;
            $path = $this->storePlaceholder($product, $sortOrder);

            $product->images()->create([
                'product_variant_id' => null,
                'image_path' => $path,
                'alt_text' => $product->name.($sortOrder === 1 ? '' : " — gallery {$sortOrder}"),
                'is_primary' => ! $hasPrimary && $i === $validCount,
                'sort_order' => $sortOrder,
            ]);

            $hasPrimary = true;
        }

        $this->ensurePrimary($product);
    }

    private function imageExists(ProductImage $image): bool
    {
        $path = (string) $image->image_path;

        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return true;
        }

        return Storage::disk('public')->exists($path);
    }

    private function ensurePrimary(Product $product): void
    {
        if ($product->images()->where('is_primary', true)->exists()) {
            return;
        }

        $first = $product->images()->orderBy('sort_order')->first();
        $first?->update(['is_primary' => true]);
    }

    private function storePlaceholder(Product $product, int $index): string
    {
        $directory = "products/{$product->id}";
        $filename = 'gallery-'.$index.'-'.Str::lower(Str::random(8)).'.svg';
        $path = "{$directory}/{$filename}";

        $palette = [
            ['#e8eef5', '#1f3a5f'],
            ['#f3eee8', '#5c4033'],
            ['#eaf4f1', '#1f5c4d'],
            ['#f5eef2', '#5a2d45'],
            ['#eef1f5', '#334155'],
        ];

        [$background, $accent] = $palette[$product->id % count($palette)];
        $label = e(Str::limit($product->name, 36, '…'));
        $subtitle = e('Sanitary placeholder '.$index);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <rect width="800" height="800" fill="{$background}"/>
  <rect x="80" y="80" width="640" height="640" rx="28" fill="#ffffff" fill-opacity="0.72" stroke="{$accent}" stroke-width="3"/>
  <circle cx="400" cy="330" r="70" fill="{$accent}" fill-opacity="0.18"/>
  <rect x="250" y="430" width="300" height="18" rx="9" fill="{$accent}" fill-opacity="0.35"/>
  <rect x="290" y="470" width="220" height="14" rx="7" fill="{$accent}" fill-opacity="0.22"/>
  <text x="400" y="560" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="28" fill="{$accent}">{$label}</text>
  <text x="400" y="600" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="18" fill="{$accent}" fill-opacity="0.7">{$subtitle}</text>
</svg>
SVG;

        Storage::disk('public')->put($path, $svg);

        return $path;
    }
}
