<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Admin\ProductVariantService;
use App\Services\Admin\ProductVariationAttributeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariationSeeder extends Seeder
{
    private const COLORS = [
        'Chrome' => '#C0C0C0',
        'Matte Black' => '#1A1A1A',
        'White' => '#FFFFFF',
        'Gold' => '#D4AF37',
        'Gun Grey' => '#4A4E53',
    ];

    private const SIZES = ['Small', 'Medium', 'Large'];

    private const COLOR_ONLY_CATEGORIES = [
        'Bathroom Faucets',
        'Kitchen Faucets',
        'Basin Mixers',
        'Kitchen Mixers',
        'Shower Mixers',
        'Health Faucets',
        'Angle Valves',
        'Shower Sets',
        'Rain Showers',
        'Hand Showers',
        'Floor Drains',
        'Flexible Pipes',
        'Soap Dispensers',
    ];

    private const COLOR_AND_SIZE_CATEGORIES = [
        'Towel Rails',
        'Corner Shelves',
        'Mirrors',
        'Kitchen Corner Shelves',
        'Kitchen Soap Dispensers',
        'Kitchen Flexible Pipes',
    ];

    public function __construct(
        private readonly ProductVariantService $variantService,
        private readonly ProductVariationAttributeService $variationAttributeService,
    ) {}

    public function run(): void
    {
        $this->call(AttributeSeeder::class);

        $products = Product::query()
            ->with(['defaultVariant', 'categories', 'variants'])
            ->where('slug', '!=', 'washroom-chair')
            ->whereHas('categories', function ($query): void {
                $query->whereIn('name', array_merge(
                    self::COLOR_ONLY_CATEGORIES,
                    self::COLOR_AND_SIZE_CATEGORIES,
                ));
            })
            ->orderBy('id')
            ->limit(30)
            ->get();

        foreach ($products as $product) {
            $this->convertToVariable($product);
        }
    }

    private function convertToVariable(Product $product): void
    {
        $baseVariant = $product->defaultVariant ?? $product->variants->first();
        if (! $baseVariant) {
            return;
        }

        $categoryName = $product->categories->first()?->name;
        $withSize = in_array($categoryName, self::COLOR_AND_SIZE_CATEGORIES, true);
        $basePrice = (float) $baseVariant->price;
        $baseSale = $baseVariant->sale_price !== null ? (float) $baseVariant->sale_price : null;
        $baseStock = max(5, (int) $baseVariant->stock_quantity);
        $baseSku = Str::upper($product->base_sku);

        $colors = array_slice(array_keys(self::COLORS), 0, 3 + ($product->id % 3)); // 3–5 colors
        $sizes = $withSize ? self::SIZES : [null];

        $rows = [];
        $sort = 0;

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $priceMultiplier = $this->priceMultiplier($color, $size);
                $price = (int) round(($basePrice * $priceMultiplier) / 50) * 50;
                $salePrice = $baseSale !== null
                    ? (int) round(($baseSale * $priceMultiplier) / 50) * 50
                    : null;

                $skuParts = [$baseSku, Str::upper(Str::slug($color))];
                if ($size) {
                    $skuParts[] = Str::upper(Str::substr($size, 0, 1));
                }

                $attributeValues = [[
                    'attribute_name' => 'Color',
                    'attribute_slug' => 'color',
                    'value' => $color,
                    'color_hex' => self::COLORS[$color],
                ]];

                if ($size) {
                    $attributeValues[] = [
                        'attribute_name' => 'Size',
                        'attribute_slug' => 'size',
                        'value' => $size,
                    ];
                }

                $rows[] = [
                    'sku' => implode('-', $skuParts),
                    'variant_name' => $size ? "{$color} / {$size}" : $color,
                    'price' => $price,
                    'sale_price' => $salePrice && $salePrice < $price ? $salePrice : null,
                    'stock_quantity' => max(0, $baseStock + (($sort % 5) * 3) - ($color === 'Gold' ? 4 : 0)),
                    'low_stock_threshold' => 5,
                    'is_default' => $sort === 0,
                    'is_active' => true,
                    'attribute_values' => $attributeValues,
                ];

                $sort++;
            }
        }

        if ($rows === []) {
            return;
        }

        $attributeMap = $this->variationAttributeService->resolveAttributeMap($rows);
        $rows = $this->variationAttributeService->normalizeVariantRows($rows, $attributeMap);

        $product->update(['product_type' => 'variable']);
        $this->variantService->syncVariable($product->fresh(['variants.images']), $rows);

        $this->attachVariantImages($product->fresh(['variants.images']));
    }

    private function priceMultiplier(string $color, ?string $size): float
    {
        $colorFactor = match ($color) {
            'Matte Black' => 1.05,
            'Gun Grey' => 1.08,
            'Gold' => 1.15,
            'White' => 1.00,
            default => 1.00,
        };

        $sizeFactor = match ($size) {
            'Small' => 0.90,
            'Large' => 1.12,
            'Medium' => 1.00,
            default => 1.00,
        };

        return $colorFactor * $sizeFactor;
    }

    private function attachVariantImages(Product $product): void
    {
        $sortOrder = (int) $product->images()->max('sort_order');

        foreach ($product->variants as $variant) {
            if ($variant->images->isNotEmpty()) {
                continue;
            }

            $colorName = $variant->color_name
                ?: trim(explode('/', (string) $variant->variant_name)[0]);
            $hex = self::COLORS[$colorName] ?? '#94A3B8';
            $label = e(Str::limit($variant->variant_name ?: $product->name, 40, '…'));

            $sortOrder++;
            $path = "products/{$product->id}/variants/{$variant->id}.svg";

            $this->ensureVariantPlaceholderFile($path, $hex, $label);

            ProductImage::query()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'image_path' => $path,
                'alt_text' => trim("{$product->name} — {$variant->variant_name}"),
                'is_primary' => false,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function ensureVariantPlaceholderFile(string $path, string $hex, string $label): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }

        Storage::disk('public')->put($path, <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <rect width="800" height="800" fill="#F8FAFC"/>
  <rect x="90" y="90" width="620" height="620" rx="28" fill="#FFFFFF" stroke="{$hex}" stroke-width="4"/>
  <circle cx="400" cy="340" r="90" fill="{$hex}"/>
  <text x="400" y="520" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="28" fill="#334155">{$label}</text>
</svg>
SVG);
    }
}
