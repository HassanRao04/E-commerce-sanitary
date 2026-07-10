<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;

class ProductVariationBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function attributesFromProduct(?Product $product): array
    {
        if (! $product?->variants) {
            return [];
        }

        $attributes = [];

        foreach ($product->variants as $variant) {
            foreach ($variant->attributeValues as $assignment) {
                $attribute = $assignment->attribute;
                if (! $attribute) {
                    continue;
                }

                $attrId = $attribute->id;
                if (! isset($attributes[$attrId])) {
                    $attributes[$attrId] = [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'slug' => $attribute->slug,
                        'values' => [],
                        'newValue' => '',
                        'newHex' => '#000000',
                    ];
                }

                $value = $assignment->attributeValue?->value ?? $assignment->custom_value;
                if (! $value) {
                    continue;
                }

                if ($attribute->isColorAttribute()) {
                    $entry = [
                        'value' => $value,
                        'hex' => VariantColorSwatch::normalizeHex($assignment->attributeValue?->color_hex) ?? '#CCCCCC',
                    ];

                    if (! self::colorValueExists($attributes[$attrId]['values'], $entry)) {
                        $attributes[$attrId]['values'][] = $entry;
                    }

                    continue;
                }

                if (! in_array($value, $attributes[$attrId]['values'], true)) {
                    $attributes[$attrId]['values'][] = $value;
                }
            }
        }

        return array_values($attributes);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function variantsFromProduct(?Product $product): array
    {
        if (! $product?->variants) {
            return [];
        }

        return $product->variants->map(function (ProductVariant $variant) {
            $image = $variant->images->sortBy('sort_order')->first();

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'variant_name' => $variant->variant_name,
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'wholesale_price' => $variant->wholesale_price,
                'dealer_price' => $variant->dealer_price,
                'cost_price' => $variant->cost_price,
                'stock_quantity' => $variant->stock_quantity,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'is_default' => $variant->is_default,
                'is_active' => $variant->is_active,
                'image_id' => $image?->id,
                'image_url' => $image?->url,
                'remove_image' => false,
                'attribute_values' => $variant->attributeValues->map(fn ($av) => [
                    'attribute_id' => $av->attribute_id,
                    'attribute_name' => $av->attribute?->name,
                    'attribute_slug' => $av->attribute?->slug,
                    'attribute_value_id' => $av->attribute_value_id,
                    'value' => $av->attributeValue?->value ?? $av->custom_value,
                    'color_hex' => VariantColorSwatch::normalizeHex($av->attributeValue?->color_hex),
                    'custom_value' => $av->custom_value,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /** @param array<int, mixed> $values */
    private static function colorValueExists(array $values, array $entry): bool
    {
        foreach ($values as $existing) {
            if (is_array($existing) && ($existing['value'] ?? null) === $entry['value']) {
                return true;
            }
        }

        return false;
    }
}
