<?php

namespace App\Support;

use App\Models\ProductVariant;

class VariantOptionFormatter
{
    /** @var list<string> */
    private const PRIORITY = ['color', 'size', 'material', 'finish'];

    /**
     * @return list<array{name: string, slug: string, value: string}>
     */
    public static function forVariant(ProductVariant $variant): array
    {
        $variant->loadMissing(['attributeValues.attribute', 'attributeValues.attributeValue']);

        $options = [];

        foreach ($variant->attributeValues as $assignment) {
            $attribute = $assignment->attribute;
            $value = $assignment->attributeValue?->value ?? $assignment->custom_value;

            if (! $attribute || ! $value) {
                continue;
            }

            $options[$attribute->slug] = [
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'value' => $value,
            ];
        }

        if (! isset($options['color']) && $variant->color_name) {
            $options['color'] = [
                'name' => 'Color',
                'slug' => 'color',
                'value' => $variant->color_name,
            ];
        }

        if (! isset($options['size']) && $variant->size) {
            $options['size'] = [
                'name' => 'Size',
                'slug' => 'size',
                'value' => $variant->size,
            ];
        }

        if (! isset($options['material']) && $variant->material) {
            $options['material'] = [
                'name' => 'Material',
                'slug' => 'material',
                'value' => $variant->material,
            ];
        }

        if (! isset($options['finish']) && $variant->finish) {
            $options['finish'] = [
                'name' => 'Finish',
                'slug' => 'finish',
                'value' => $variant->finish,
            ];
        }

        return self::sortOptions(array_values($options));
    }

    /**
     * @param  list<array{name: string, slug: string, value: string}>  $options
     */
    public static function label(array $options): string
    {
        if ($options === []) {
            return '';
        }

        return collect($options)
            ->map(fn (array $option) => "{$option['name']}: {$option['value']}")
            ->implode(' · ');
    }

    /**
     * @param  list<array{name: string, slug: string, value: string}>|null  $options
     */
    public static function labelOrFallback(?array $options, ?string $fallback = null): string
    {
        $label = self::label($options ?? []);

        return $label !== '' ? $label : (string) ($fallback ?? '');
    }

    /**
     * @param  list<array{name: string, slug: string, value: string}>  $options
     * @return list<array{name: string, slug: string, value: string}>
     */
    private static function sortOptions(array $options): array
    {
        usort($options, function (array $a, array $b): int {
            $orderA = array_search($a['slug'], self::PRIORITY, true);
            $orderB = array_search($b['slug'], self::PRIORITY, true);
            $orderA = $orderA === false ? 999 : $orderA;
            $orderB = $orderB === false ? 999 : $orderB;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp($a['name'], $b['name']);
        });

        return $options;
    }
}
