<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function syncSimple(Product $product, array $data): ProductVariant
    {
        $payload = [
            'sku' => $product->base_sku,
            'variant_name' => 'Default',
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
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
            ->delete();

        return $variant;
    }

    public function syncVariable(Product $product, array $variants): void
    {
        $keptIds = [];
        $defaultId = null;

        foreach ($variants as $index => $row) {
            $variant = ! empty($row['id'])
                ? $product->variants()->findOrFail($row['id'])
                : new ProductVariant(['product_id' => $product->id]);

            $variant->fill([
                'sku' => Str::upper(trim($row['sku'])),
                'variant_name' => $row['variant_name'],
                'price' => $row['price'],
                'sale_price' => $row['sale_price'] ?? null,
                'cost_price' => $row['cost_price'] ?? null,
                'stock_quantity' => (int) $row['stock_quantity'],
                'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? config('shop.low_stock_threshold', 5)),
                'is_default' => ! empty($row['is_default']),
                'is_active' => array_key_exists('is_active', $row) ? ! empty($row['is_active']) : true,
                'sort_order' => $index,
            ])->save();

            $keptIds[] = $variant->id;

            if ($variant->is_default) {
                $defaultId = $variant->id;
            }

            $this->syncVariantAttributes($variant, $row['attribute_values'] ?? []);
            $this->syncInventory($variant, (int) $variant->stock_quantity);
        }

        if (! $defaultId && ! empty($keptIds)) {
            $defaultId = $keptIds[0];
            ProductVariant::whereKey($defaultId)->update(['is_default' => true]);
        }

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $keptIds)
            ->delete();

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

    public function syncInventory(ProductVariant $variant, int $quantity): void
    {
        $warehouse = Warehouse::query()->where('is_default', true)->first()
            ?? Warehouse::query()->where('is_active', true)->first();

        if (! $warehouse) {
            return;
        }

        $warehouse->inventoryItems()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'quantity_on_hand' => $quantity,
                'quantity_reserved' => 0,
                'low_stock_threshold' => $variant->low_stock_threshold ?? config('shop.low_stock_threshold', 5),
            ]
        );
    }
}
