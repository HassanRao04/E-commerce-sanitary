<?php

namespace App\Services;

use App\Models\ProductVariant;

/**
 * @deprecated Use InventoryControlService directly.
 */
class StockAvailabilityService
{
    public function __construct(
        private readonly InventoryControlService $inventory,
    ) {}

    public function availableQuantity(ProductVariant $variant, int $heldInCurrentCart = 0): int
    {
        return $this->inventory->availableQuantity($variant, $heldInCurrentCart);
    }

    public function defaultWarehouse(): ?\App\Models\Warehouse
    {
        return $this->inventory->defaultWarehouse();
    }

    public function syncVariantAggregateStock(int $variantId): void
    {
        $this->inventory->syncVariantAggregate($variantId);
    }
}
