<?php

namespace App\Support;

use App\Models\AttributeValue;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class VariantColorSwatch
{
    public static function normalizeHex(?string $hex): ?string
    {
        if ($hex === null || trim($hex) === '') {
            return null;
        }

        $hex = strtoupper(trim($hex));

        if (! str_starts_with($hex, '#')) {
            $hex = '#'.$hex;
        }

        if (! preg_match('/^#[0-9A-F]{6}$/', $hex)) {
            return null;
        }

        return $hex;
    }

    public static function isColorAttributeSlug(?string $slug): bool
    {
        return $slug !== null && strtolower($slug) === 'color';
    }

    /**
     * Resolve swatch data for a variant (for future storefront use).
     *
     * @return array{name: string, hex: string}|null
     */
    public static function forVariant(ProductVariant $variant): ?array
    {
        $variant->loadMissing(['attributeValues.attribute', 'attributeValues.attributeValue']);

        foreach ($variant->attributeValues as $assignment) {
            $slug = $assignment->attribute?->slug;
            $type = $assignment->attribute?->type;

            if (! self::isColorAttributeSlug($slug) && $type !== 'color') {
                continue;
            }

            $name = $assignment->attributeValue?->value ?? $assignment->custom_value;
            $hex = self::normalizeHex($assignment->attributeValue?->color_hex)
                ?? self::normalizeHex($variant->color_hex);

            if ($name && $hex) {
                return [
                    'name' => $name,
                    'hex' => $hex,
                ];
            }

            if ($name) {
                return [
                    'name' => $name,
                    'hex' => self::normalizeHex($variant->color_hex) ?? '#CCCCCC',
                ];
            }
        }

        $name = $variant->color_name;
        $hex = self::normalizeHex($variant->color_hex);

        if ($name && $hex) {
            return [
                'name' => $name,
                'hex' => $hex,
            ];
        }

        return null;
    }

    /**
     * @param  Collection<int, ProductVariant>|iterable<int, ProductVariant>  $variants
     * @return array<int, array{id: int, name: string, hex: string, label: string}>
     */
    public static function mapForVariants(iterable $variants): array
    {
        $mapped = [];

        foreach ($variants as $variant) {
            $swatch = self::forVariant($variant);

            if (! $swatch) {
                continue;
            }

            $mapped[] = [
                'id' => $variant->id,
                'name' => $swatch['name'],
                'hex' => $swatch['hex'],
                'label' => $swatch['name'],
            ];
        }

        return $mapped;
    }

    /**
     * @return array<string, string>
     */
    public static function defaultPalette(): array
    {
        return [
            'Black' => '#000000',
            'White' => '#FFFFFF',
            'Gold' => '#D4AF37',
        ];
    }

    public static function hexForAttributeValue(?AttributeValue $value): ?string
    {
        return self::normalizeHex($value?->color_hex);
    }
}
