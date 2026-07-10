<?php

namespace App\Services\Admin;

use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Services\ActivityLogService;
use App\Services\InventoryControlService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventory,
        private readonly ActivityLogService $activityLog,
        private readonly InventoryControlService $inventoryControl,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->inventory->search($filters['q'] ?? null, $filters, $perPage);
    }

    public function adjust(Inventory $item, StockMovementType $type, int $quantity, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($item, $type, $quantity, $notes) {
            $signedQuantity = match ($type) {
                StockMovementType::Sale,
                StockMovementType::TransferOut,
                StockMovementType::Reserved => -abs($quantity),
                default => abs($quantity),
            };

            $oldOnHand = $item->quantity_on_hand;
            $newOnHand = $oldOnHand + $signedQuantity;

            if ($newOnHand < 0) {
                throw new \InvalidArgumentException('Insufficient stock for this adjustment.');
            }

            $item->update(['quantity_on_hand' => $newOnHand]);

            StockMovement::create([
                'warehouse_id' => $item->warehouse_id,
                'product_variant_id' => $item->product_variant_id,
                'movement_type' => $type,
                'quantity' => $signedQuantity,
                'balance_after' => $newOnHand,
                'reference_type' => Inventory::class,
                'reference_id' => $item->id,
                'notes' => $notes,
                'performed_by' => auth()->id(),
            ]);

            $this->syncVariantStock($item->product_variant_id);

            $this->activityLog->log('inventory.adjusted', $item, [
                'quantity_on_hand' => $oldOnHand,
            ], [
                'quantity_on_hand' => $newOnHand,
                'movement_type' => $type->value,
            ]);

            return $item->fresh(['warehouse', 'productVariant.product']);
        });
    }

    private function syncVariantStock(int $variantId): void
    {
        $this->inventoryControl->syncVariantAggregate($variantId);
    }
}
