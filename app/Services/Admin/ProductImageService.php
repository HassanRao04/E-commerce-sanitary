<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageService
{
    public function uploadMany(Product $product, array $files, ?int $variantId = null): void
    {
        $sortOrder = (int) $product->images()->max('sort_order');

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $sortOrder++;
            $path = $file->store("products/{$product->id}", 'public');

            $product->images()->create([
                'product_variant_id' => $variantId,
                'image_path' => $path,
                'alt_text' => $product->name,
                'is_primary' => ! $product->images()->exists(),
                'sort_order' => $sortOrder,
            ]);
        }
    }

    public function syncFromRequest(Product $product, array $data): void
    {
        foreach ($data['remove_image_ids'] ?? [] as $imageId) {
            $image = $product->images()->find($imageId);
            if ($image) {
                $this->delete($image);
            }
        }

        if (! empty($data['images'])) {
            $this->uploadMany($product, $data['images']);
        }

        if (! empty($data['primary_image_id'])) {
            $this->setPrimary($product, (int) $data['primary_image_id']);
        }
    }

    public function setPrimary(Product $product, int $imageId): void
    {
        $product->images()->update(['is_primary' => false]);
        $product->images()->whereKey($imageId)->update(['is_primary' => true]);
    }

    public function delete(ProductImage $image): void
    {
        if ($image->image_path && ! str_starts_with($image->image_path, 'http')) {
            Storage::disk('public')->delete($image->image_path);
        }

        $wasPrimary = $image->is_primary;
        $productId = $image->product_id;
        $image->delete();

        if ($wasPrimary) {
            $next = ProductImage::query()->where('product_id', $productId)->orderBy('sort_order')->first();
            $next?->update(['is_primary' => true]);
        }
    }

    public function reorder(Product $product, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $product->images()->whereKey($id)->update(['sort_order' => $index + 1]);
        }
    }
}
