<?php

namespace App\Services\Admin;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Support\VariantColorSwatch;
use Illuminate\Support\Str;

class ProductVariationAttributeService
{
    /**
     * Ensure attributes and values exist for all builder rows.
     *
     * @param  array<int, array<string, mixed>>  $variants
     * @return array<string, array{id: int, name: string, slug: string, values: array<string, int>}>
     */
    public function resolveAttributeMap(array $variants): array
    {
        $map = [];

        foreach ($variants as $variant) {
            foreach ($variant['attribute_values'] ?? [] as $row) {
                $name = trim((string) ($row['attribute_name'] ?? ''));
                $value = trim((string) ($row['value'] ?? $row['custom_value'] ?? ''));

                if ($name === '' || $value === '') {
                    continue;
                }

                $slug = $this->normalizeSlug($row['attribute_slug'] ?? null, $name);
                $isColor = VariantColorSwatch::isColorAttributeSlug($slug);
                $colorHex = $isColor ? VariantColorSwatch::normalizeHex($row['color_hex'] ?? null) : null;

                $attribute = Attribute::query()->firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'type' => $isColor ? 'color' : 'select',
                        'is_filterable' => true,
                        'is_variant_attribute' => true,
                        'sort_order' => ((int) Attribute::query()->max('sort_order')) + 1,
                    ]
                );

                $updates = [];

                if (! $attribute->is_variant_attribute) {
                    $updates['is_variant_attribute'] = true;
                }

                if ($isColor && $attribute->type !== 'color') {
                    $updates['type'] = 'color';
                }

                if ($updates !== []) {
                    $attribute->update($updates);
                }

                $valueSlug = Str::slug($value) ?: Str::slug($name.'-value');
                $valuePayload = [
                    'value' => $value,
                    'sort_order' => ((int) $attribute->values()->max('sort_order')) + 1,
                ];

                if ($colorHex) {
                    $valuePayload['color_hex'] = $colorHex;
                }

                $attributeValue = AttributeValue::query()->firstOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'slug' => $valueSlug,
                    ],
                    $valuePayload
                );

                if ($colorHex && $attributeValue->color_hex !== $colorHex) {
                    $attributeValue->update(['color_hex' => $colorHex]);
                }

                if (! isset($map[$slug])) {
                    $map[$slug] = [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'slug' => $slug,
                        'values' => [],
                    ];
                }

                $map[$slug]['values'][$value] = $attributeValue->id;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @param  array<string, array{id: int, name: string, slug: string, values: array<string, int>}>  $attributeMap
     * @return array<int, array<string, mixed>>
     */
    public function normalizeVariantRows(array $variants, array $attributeMap): array
    {
        return array_map(function (array $variant) use ($attributeMap) {
            $normalized = [];

            foreach ($variant['attribute_values'] ?? [] as $row) {
                if (! empty($row['attribute_id']) && ! empty($row['attribute_value_id'])) {
                    $colorHex = VariantColorSwatch::normalizeHex($row['color_hex'] ?? null);

                    if ($colorHex) {
                        AttributeValue::query()
                            ->whereKey($row['attribute_value_id'])
                            ->update(['color_hex' => $colorHex]);
                    }

                    $normalized[] = [
                        'attribute_id' => (int) $row['attribute_id'],
                        'attribute_value_id' => (int) $row['attribute_value_id'],
                        'custom_value' => null,
                        'color_hex' => $colorHex,
                    ];

                    continue;
                }

                $name = trim((string) ($row['attribute_name'] ?? ''));
                $value = trim((string) ($row['value'] ?? $row['custom_value'] ?? ''));

                if ($name === '' || $value === '') {
                    continue;
                }

                $slug = $this->normalizeSlug($row['attribute_slug'] ?? null, $name);
                $attr = $attributeMap[$slug] ?? null;

                if (! $attr) {
                    continue;
                }

                $valueId = $attr['values'][$value] ?? null;

                $normalized[] = [
                    'attribute_id' => $attr['id'],
                    'attribute_value_id' => $valueId,
                    'custom_value' => $valueId ? null : $value,
                    'color_hex' => VariantColorSwatch::normalizeHex($row['color_hex'] ?? null),
                ];
            }

            $variant['attribute_values'] = $normalized;

            return $variant;
        }, $variants);
    }

    private function normalizeSlug(?string $slug, string $name): string
    {
        $slug = Str::slug($slug ?: $name);

        return $slug !== '' ? $slug : 'attribute-'.Str::random(6);
    }
}
