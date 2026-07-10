<?php

namespace App\Services;

use App\Models\ProductVariant;

class InventoryStockService
{
    public function __construct(
        private readonly InventoryControlService $inventory,
    ) {}

    public function decrementForSale(
        ProductVariant $variant,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): void {
        $this->inventory->commitSale($variant, $quantity, $referenceType, $referenceId, $notes);
    }
}
