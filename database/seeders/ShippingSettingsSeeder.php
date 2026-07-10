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
        $settings = ShippingSetting::current();

        $settings->update([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 100,
            'product_rate_enabled' => true,
            'category_rate_enabled' => true,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 5000,
            'default_method' => ShippingMethod::Flat,
        ]);

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
                ['amount' => 350, 'is_active' => true],
            );
        }

        app(ShippingSettingsService::class)->clearCache();
    }
}
