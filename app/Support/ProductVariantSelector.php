<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\InventoryControlService;
use App\Services\ProductPricingService;
use Illuminate\Support\Collection;

class ProductVariantSelector
{
    /** @var list<string> */
    private const AXIS_ORDER = ['color', 'size', 'material', 'finish'];

    /**
     * @return array{
     *     variants: list<array<string, mixed>>,
     *     axes: list<array<string, mixed>>,
     *     hasMultipleVariants: bool,
     *     useAxisSelector: bool,
     *     defaultVariantId: int|null,
     *     gallery: array<string, mixed>
     * }
     */
    public static function forProduct(Product $product): array
    {
        $product->loadMissing(['images']);

        $variants = self::resolveVariants($product);
        $inventory = app(InventoryControlService::class);

        $fallbackImages = self::sharedProductImages($product);
        $axisMap = [];
        $variantRows = [];
        $imagesByVariant = [];

        foreach ($variants as $variant) {
            $options = self::optionsForVariant($variant);
            $galleryImages = self::galleryImagesForVariant($variant, $product, $fallbackImages);

            $imagesByVariant[$variant->id] = $galleryImages;

            foreach ($options as $slug => $option) {
                if (! isset($axisMap[$slug])) {
                    $axisMap[$slug] = [
                        'slug' => $slug,
                        'name' => $option['name'],
                        'type' => $option['type'],
                        'values' => [],
                    ];
                }

                $axisMap[$slug]['values'][$option['value']] = array_filter([
                    'value' => $option['value'],
                    'hex' => $option['hex'] ?? null,
                ], fn ($value) => $value !== null);
            }

            $swatch = VariantColorSwatch::forVariant($variant);
            $label = $variant->variant_name ?: trim(collect($options)->pluck('value')->filter()->join(' · ')) ?: $variant->sku;
            $pricing = app(ProductPricingService::class)->selectorPayload($variant);
            $stockSnapshot = $inventory->snapshot($variant);

            $variantRows[] = array_merge([
                'id' => $variant->id,
                'label' => $label,
                'sku' => $variant->sku,
                'options' => collect($options)->mapWithKeys(fn (array $option, string $slug) => [$slug => $option['value']])->all(),
                'stock' => $stockSnapshot['available'],
                'on_hand' => $stockSnapshot['on_hand'],
                'reserved' => $stockSnapshot['reserved'],
                'stock_status' => $stockSnapshot['status'],
                'colorHex' => $swatch['hex'] ?? VariantColorSwatch::normalizeHex($variant->color_hex),
                'colorName' => $swatch['name'] ?? $variant->color_name,
                'purchasable' => $stockSnapshot['available'] > 0,
                'images' => $galleryImages,
            ], $pricing);
        }

        $axes = self::sortAxes(array_map(
            fn (array $axis) => [
                'slug' => $axis['slug'],
                'name' => $axis['name'],
                'type' => $axis['type'],
                'options' => array_values($axis['values']),
            ],
            array_values($axisMap),
        ));

        $defaultVariant = $variants->firstWhere('id', $product->default_variant_id) ?? $variants->first();
        $defaultVariantId = $defaultVariant?->id;
        $initialImages = $defaultVariantId
            ? ($imagesByVariant[$defaultVariantId] ?? $fallbackImages)
            : $fallbackImages;

        return [
            'variants' => $variantRows,
            'axes' => $axes,
            'hasMultipleVariants' => count($variantRows) > 1,
            'useAxisSelector' => count($axes) > 0 && count($variantRows) > 1,
            'defaultVariantId' => $defaultVariantId,
            'gallery' => [
                'initialImages' => $initialImages,
                'fallbackImages' => $fallbackImages,
                'imagesByVariant' => $imagesByVariant,
                'defaultVariantId' => $defaultVariantId,
            ],
        ];
    }

    /** @return Collection<int, ProductVariant> */
    private static function resolveVariants(Product $product): Collection
    {
        if ($product->relationLoaded('variants')) {
            $product->variants->loadMissing([
                'images',
                'attributeValues.attribute',
                'attributeValues.attributeValue',
            ]);

            return $product->variants;
        }

        return $product->variants()
            ->where('is_active', true)
            ->with(['images', 'attributeValues.attribute', 'attributeValues.attributeValue'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return list<array{url: string, alt: string}>
     */
    public static function galleryImagesForVariant(
        ProductVariant $variant,
        Product $product,
        ?array $fallbackImages = null,
    ): array {
        $fallbackImages ??= self::sharedProductImages($product);

        $variantImages = self::mapImages(
            $variant->images->sortByDesc('is_primary')->sortBy('sort_order')->values(),
            $product->name.' — '.$variant->variant_name,
        );

        if ($variantImages !== []) {
            return $variantImages;
        }

        return $fallbackImages;
    }

    /**
     * @return list<array{url: string, alt: string}>
     */
    public static function sharedProductImages(Product $product): array
    {
        $shared = $product->images
            ->whereNull('product_variant_id')
            ->sortByDesc('is_primary')
            ->sortBy('sort_order')
            ->values();

        $images = self::mapImages($shared, $product->name);

        if ($images !== []) {
            return $images;
        }

        $images = self::mapImages(
            $product->images->sortByDesc('is_primary')->sortBy('sort_order')->values(),
            $product->name,
        );

        if ($images !== []) {
            return $images;
        }

        return [[
            'url' => $product->primary_image_url,
            'alt' => $product->name,
        ]];
    }

    /**
     * @param  Collection<int, ProductImage>  $images
     * @return list<array{url: string, alt: string}>
     */
    private static function mapImages(Collection $images, string $defaultAlt): array
    {
        return $images
            ->map(fn (ProductImage $image) => [
                'url' => $image->url,
                'alt' => $image->alt_text ?: $defaultAlt,
            ])
            ->values()
            ->all();
    }

    /** @return array<string, array{name: string, type: string, value: string, hex: string|null}> */
    private static function optionsForVariant(ProductVariant $variant): array
    {
        $options = [];

        foreach ($variant->attributeValues as $assignment) {
            $attribute = $assignment->attribute;

            if (! $attribute?->is_variant_attribute) {
                continue;
            }

            $value = $assignment->attributeValue?->value ?? $assignment->custom_value;

            if (! $value) {
                continue;
            }

            $slug = $attribute->slug;
            $isColor = $attribute->isColorAttribute();

            $options[$slug] = [
                'name' => $attribute->name,
                'type' => $isColor ? 'color' : 'select',
                'value' => $value,
                'hex' => $isColor
                    ? (VariantColorSwatch::hexForAttributeValue($assignment->attributeValue)
                        ?? VariantColorSwatch::normalizeHex($variant->color_hex))
                    : null,
            ];
        }

        if (! isset($options['color']) && $variant->color_name) {
            $options['color'] = [
                'name' => 'Color',
                'type' => 'color',
                'value' => $variant->color_name,
                'hex' => VariantColorSwatch::normalizeHex($variant->color_hex),
            ];
        }

        if (! isset($options['size']) && $variant->size) {
            $options['size'] = [
                'name' => 'Size',
                'type' => 'select',
                'value' => $variant->size,
                'hex' => null,
            ];
        }

        if (! isset($options['finish']) && $variant->finish) {
            $options['finish'] = [
                'name' => 'Finish',
                'type' => 'select',
                'value' => $variant->finish,
                'hex' => null,
            ];
        }

        return $options;
    }

    /** @param list<array<string, mixed>> $axes */
    private static function sortAxes(array $axes): array
    {
        usort($axes, function (array $a, array $b): int {
            $orderA = array_search($a['slug'], self::AXIS_ORDER, true);
            $orderB = array_search($b['slug'], self::AXIS_ORDER, true);
            $orderA = $orderA === false ? 999 : $orderA;
            $orderB = $orderB === false ? 999 : $orderB;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp((string) $a['name'], (string) $b['name']);
        });

        return $axes;
    }
}
