<?php

namespace App\Http\Resources\Api\V1;

use App\Services\InventoryControlService;
use App\Services\ProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductVariant */
class ProductVariantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $quote = app(ProductPricingService::class)->forVariant($this->resource);
        $inventory = app(InventoryControlService::class)->snapshot($this->resource);

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->variant_name,
            'base_price' => $quote['base_price'],
            'sale_price' => $quote['sale_price'],
            'wholesale_price' => $quote['wholesale_price'],
            'dealer_price' => $quote['dealer_price'],
            'display_price' => $quote['display_price'],
            'compare_price' => $quote['compare_price'],
            'price_type' => $quote['price_type'],
            'in_stock' => $inventory['available'] > 0,
            'stock_quantity' => $inventory['available'],
            'on_hand' => $inventory['on_hand'],
            'reserved' => $inventory['reserved'],
            'stock_status' => $inventory['status'],
        ];
    }
}
