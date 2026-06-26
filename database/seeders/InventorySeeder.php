<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $main = Warehouse::where('code', 'MAIN')->first();
        $lahore = Warehouse::where('code', 'LHR')->first();

        if (! $main) {
            return;
        }

        ProductVariant::query()->each(function (ProductVariant $variant) use ($main, $lahore) {
            Inventory::updateOrCreate(
                ['warehouse_id' => $main->id, 'product_variant_id' => $variant->id],
                [
                    'quantity_on_hand' => $variant->stock_quantity,
                    'quantity_reserved' => 0,
                    'low_stock_threshold' => config('shop.low_stock_threshold', 5),
                ]
            );

            if ($lahore) {
                Inventory::updateOrCreate(
                    ['warehouse_id' => $lahore->id, 'product_variant_id' => $variant->id],
                    [
                        'quantity_on_hand' => (int) max(5, $variant->stock_quantity / 2),
                        'quantity_reserved' => 0,
                        'low_stock_threshold' => 5,
                    ]
                );
            }
        });
    }
}
