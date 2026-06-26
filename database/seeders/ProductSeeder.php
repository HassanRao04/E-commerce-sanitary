<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            ['name' => 'Basin Mixer Falcon Chrome', 'sku' => 'BM-FAL-001', 'brand' => 'Falcon', 'price' => 8500, 'categories' => ['Basin Mixers']],
            ['name' => 'Wall Hung Basin Rozana White', 'sku' => 'WHB-ROZ-002', 'brand' => 'Rozana', 'price' => 6200, 'categories' => ['Wall Hung Basins']],
            ['name' => 'Rain Shower Head Grohe 200', 'sku' => 'RS-GRO-003', 'brand' => 'Grohe', 'price' => 14500, 'categories' => ['Rain Showers']],
            ['name' => 'One Piece Toilet Kohler Arc', 'sku' => 'OPT-KOH-004', 'brand' => 'Kohler', 'price' => 32000, 'categories' => ['One Piece']],
            ['name' => 'Kitchen Mixer Jaquar Pro', 'sku' => 'KM-JAQ-005', 'brand' => 'Jaquar', 'price' => 11800, 'categories' => ['Kitchen Mixers']],
            ['name' => 'Towel Rail Master SS', 'sku' => 'TR-MST-006', 'brand' => 'Master', 'price' => 4500, 'categories' => ['Towel Rails']],
        ];

        foreach ($samples as $index => $sample) {
            $brand = Brand::where('slug', Str::slug($sample['brand']))->first();
            $slug = Str::slug($sample['name']);

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'brand_id' => $brand?->id,
                    'name' => $sample['name'],
                    'base_sku' => $sample['sku'],
                    'product_type' => 'simple',
                    'status' => ProductStatus::Active,
                    'short_description' => 'Premium sanitary ware for modern bathrooms.',
                    'is_featured' => $index < 2,
                    'is_new_arrival' => $index === 0,
                ]
            );

            $variant = ProductVariant::updateOrCreate(
                ['sku' => $sample['sku']],
                [
                    'product_id' => $product->id,
                    'variant_name' => 'Default',
                    'price' => $sample['price'],
                    'stock_quantity' => 25,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );

            $product->update(['default_variant_id' => $variant->id]);

            $categoryIds = Category::query()
                ->whereIn('name', $sample['categories'])
                ->pluck('id');

            $product->categories()->sync($categoryIds);
        }
    }
}
