<?php

namespace Database\Seeders;

use App\Enums\ShippingMethod;
use App\Models\Category;
use App\Models\CategoryShippingRate;
use App\Models\Product;
use App\Models\ProductShippingRate;
use App\Models\ShippingSetting;
use App\Services\ShippingSettingsService;
use Illuminate\Database\Seeder;

class ShippingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // sync() enables only the selected default method (Flat here).
        app(ShippingSettingsService::class)->sync(
            [
                'flat_rate_amount' => 300,
                'free_shipping_enabled' => true,
                'free_shipping_threshold' => 10000,
                'default_method' => ShippingMethod::Flat,
            ],
            [],
            [],
        );

        $category = Category::query()
            ->where('name', 'Basins & Sinks')
            ->first();

        if ($category) {
            CategoryShippingRate::updateOrCreate(
                ['category_id' => $category->id],
                ['amount' => 200, 'is_active' => true],
            );
        }

        $product = Product::query()
            ->where('name', 'Wall Hung Basin Rozana White')
            ->first();

        if ($product) {
            ProductShippingRate::updateOrCreate(
                ['product_id' => $product->id],
                ['amount' => 350, 'free_shipping' => false, 'is_active' => true],
            );
        }

        // Rate rows were written outside sync(); refresh rate caches.
        app(ShippingSettingsService::class)->clearCache();

        // Ensure model reference stays valid for callers that expect a row.
        ShippingSetting::current();
    }
}
