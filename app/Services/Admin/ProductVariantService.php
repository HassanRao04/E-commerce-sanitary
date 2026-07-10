<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Warehouse;
use App\Services\InventoryControlService;
use App\Support\VariantColorSwatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function __construct(
        private readonly ProductImageService $imageService,
        private readonly InventoryControlService $inventory,
    ) {}

    public function syncSimple(Product $product, array $data): ProductVariant
    {
        $payload = [
            'sku' => $product->base_sku,
            'variant_name' => 'Default',
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'wholesale_price' => $data['wholesale_price'] ?? null,
            'dealer_price' => $data['dealer_price'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,
            'stock_quantity' => (int) $data['stock_quantity'],
            'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? config('shop.low_stock_threshold', 5)),
            'is_default' => true,
            'is_active' => true,
        ];

        $variant = $product->defaultVariant
            ?? $product->variants()->where('is_default', true)->first()
            ?? new ProductVariant(['product_id' => $product->id]);

        $variant->fill($payload)->save();
        $product->update(['default_variant_id' => $variant->id]);

        $this->syncInventory($variant, (int) $payload['stock_quantity']);

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereKeyNot($variant->id)
            ->forceDelete();

        return $variant;
    }

    public function syncVariable(Product $product, array $variants): void
    {
        $keptIds = [];
        $defaultId = null;
        $existingVariants = $product->variants()->with('images')->get()->keyBy('id');
        $warehouse = Warehouse::query()->where('is_default', true)->first()
            ?? Warehouse::query()->where('is_active', true)->first();

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->update(['is_default' => false]);

        foreach ($variants as $index => $row) {
            $variantName = trim((string) ($row['variant_name'] ?? ''));
            if ($variantName === '') {
                $variantName = $this->buildVariantName($row['attribute_values'] ?? []);
            }

            $variant = ! empty($row['id'])
                ? ($existingVariants->get($row['id']) ?? abort(404))
                : new ProductVariant(['product_id' => $product->id]);

            $isDefault = ! empty($row['is_default']);

            $variant->fill([
                'sku' => Str::upper(trim($row['sku'])),
                'variant_name' => $variantName !== '' ? $variantName : 'Variant',
                'price' => $row['price'],
                'sale_price' => $row['sale_price'] ?? null,
                'wholesale_price' => $row['wholesale_price'] ?? null,
                'dealer_price' => $row['dealer_price'] ?? null,
                'cost_price' => $row['cost_price'] ?? null,
                'stock_quantity' => (int) $row['stock_quantity'],
                'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? config('shop.low_stock_threshold', 5)),
                'is_default' => $isDefault,
                'is_active' => array_key_exists('is_active', $row) ? ! empty($row['is_active']) : true,
                'sort_order' => $index,
            ])->save();

            $keptIds[] = $variant->id;

            if ($isDefault) {
                $defaultId = $variant->id;
            }

            $this->syncVariantAttributes($variant, $row['attribute_values'] ?? []);
            $this->mirrorLegacyAttributes($variant);
            $this->syncInventory($variant, (int) $variant->stock_quantity, $warehouse);
            $this->syncVariantImage($product, $variant, $row);
        }

        if (! $defaultId && ! empty($keptIds)) {
            $defaultId = $keptIds[0];
            ProductVariant::whereKey($defaultId)->update(['is_default' => true]);
        } elseif ($defaultId) {
            ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereKeyNot($defaultId)
                ->update(['is_default' => false]);
        }

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $keptIds)
            ->forceDelete();

        if ($defaultId) {
            $product->update(['default_variant_id' => $defaultId]);
        }
    }

    public function syncVariantAttributes(ProductVariant $variant, array $attributes): void
    {
        $variant->attributeValues()->delete();

        foreach ($attributes as $row) {
            if (empty($row['attribute_id'])) {
                continue;
            }

            if (empty($row['attribute_value_id']) && empty($row['custom_value'])) {
                continue;
            }

            ProductVariantAttributeValue::create([
                'product_variant_id' => $variant->id,
                'attribute_id' => $row['attribute_id'],
                'attribute_value_id' => $row['attribute_value_id'] ?? null,
                'custom_value' => $row['custom_value'] ?? null,
            ]);
        }
    }

    public function mirrorLegacyAttributes(ProductVariant $variant): void
    {
        $variant->loadMissing(['attributeValues.attribute', 'attributeValues.attributeValue']);

        $updates = [];

        foreach ($variant->attributeValues as $assignment) {
            $slug = $assignment->attribute?->slug;
            $value = $assignment->attributeValue?->value ?? $assignment->custom_value;

            if (! $slug || ! $value) {
                continue;
            }

            match ($slug) {
                'color' => $this->applyColorLegacyFields($updates, $value, $assignment),
                'size' => $updates['size'] = $value,
                'material' => $updates['material'] = $value,
                'finish' => $updates['finish'] = $value,
                default => null,
            };
        }

        if ($updates !== []) {
            $variant->update($updates);
        }
    }

    /** @param array<string, mixed> $updates */
    private function applyColorLegacyFields(array &$updates, string $value, ProductVariantAttributeValue $assignment): void
    {
        $updates['color_name'] = $value;
        $updates['color_hex'] = VariantColorSwatch::normalizeHex($assignment->attributeValue?->color_hex);
    }

    public function syncInventory(ProductVariant $variant, int $quantity, ?Warehouse $warehouse = null): void
    {
        unset($warehouse);

        $this->inventory->setOnHand(
            $variant,
            $quantity,
            $variant->low_stock_threshold,
        );
    }

    /** @param array<string, mixed> $row */
    private function syncVariantImage(Product $product, ProductVariant $variant, array $row): void
    {
        if (! empty($row['remove_image'])) {
            foreach ($variant->images as $image) {
                $this->imageService->delete($image);
            }

            return;
        }

        $image = $row['image'] ?? null;
        if ($image instanceof UploadedFile) {
            $this->imageService->uploadForVariant($product, $variant, $image);
        }
    }

    /** @param array<int, array<string, mixed>> $attributes */
    private function buildVariantName(array $attributes): string
    {
        $labels = [];

        foreach ($attributes as $row) {
            $label = trim((string) ($row['value'] ?? $row['custom_value'] ?? ''));
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return implode(' / ', $labels);
    }
}
