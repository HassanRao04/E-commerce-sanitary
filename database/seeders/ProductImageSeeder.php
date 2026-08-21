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

            if (! str_starts_with((string) $image->image_path, 'http')) {
                $this->deleteLocalFile($image->image_path);
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

        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        for ($slot = $validCount + 1; $slot <= $targetCount; $slot++) {
            $path = $this->placeholderPath($product, $slot);
            $this->ensurePlaceholderFile($product, $slot, $path);

            $product->images()->create([
                'product_variant_id' => null,
                'image_path' => $path,
                'alt_text' => $product->name.($slot === 1 ? '' : " — gallery {$slot}"),
                'is_primary' => ! $hasPrimary && $slot === ($validCount + 1),
                'sort_order' => $slot,
            ]);

            $hasPrimary = true;
        }

        $this->ensurePrimary($product);
    }

    private function placeholderPath(Product $product, int $slot): string
    {
        return "products/{$product->id}/gallery-{$slot}.svg";
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

    private function ensurePlaceholderFile(Product $product, int $slot, string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }

        $palette = [
            ['#e8eef5', '#1f3a5f'],
            ['#f3eee8', '#5c4033'],
            ['#eaf4f1', '#1f5c4d'],
            ['#f5eef2', '#5a2d45'],
            ['#eef1f5', '#334155'],
        ];

        [$background, $accent] = $palette[$product->id % count($palette)];
        $label = e(Str::limit($product->name, 36, '…'));
        $subtitle = e('Sanitary placeholder '.$slot);

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
    }

    private function deleteLocalFile(?string $path): void
    {
        if (blank($path) || str_starts_with((string) $path, 'http')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
