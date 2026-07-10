<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryControlService
{
    private ?Warehouse $defaultWarehouse = null;

    /**
     * @return array{
     *     on_hand: int,
     *     reserved: int,
     *     available: int,
     *     low_stock_threshold: int,
     *     status: string
     * }
     */
    public function snapshot(ProductVariant $variant): array
    {
        $item = $this->inventoryItem($variant);

        if ($item) {
            return [
                'on_hand' => (int) $item->quantity_on_hand,
                'reserved' => (int) $item->quantity_reserved,
                'available' => (int) $item->available_quantity,
                'low_stock_threshold' => (int) $item->low_stock_threshold,
                'status' => (string) $item->stock_status,
            ];
        }

        $onHand = max(0, (int) $variant->stock_quantity);

        return [
            'on_hand' => $onHand,
            'reserved' => 0,
            'available' => $onHand,
            'low_stock_threshold' => (int) ($variant->low_stock_threshold ?? config('shop.low_stock_threshold', 5)),
            'status' => match (true) {
                $onHand <= 0 => 'out_of_stock',
                $onHand <= (int) ($variant->low_stock_threshold ?? config('shop.low_stock_threshold', 5)) => 'low_stock',
                default => 'in_stock',
            },
        ];
    }

    public function availableQuantity(ProductVariant $variant, int $heldInCurrentCart = 0): int
    {
        $snapshot = $this->snapshot($variant);

        return max(0, $snapshot['available'] + max(0, $heldInCurrentCart));
    }

    public function reservedQuantity(ProductVariant $variant): int
    {
        return $this->snapshot($variant)['reserved'];
    }

    public function onHandQuantity(ProductVariant $variant): int
    {
        return $this->snapshot($variant)['on_hand'];
    }

    public function stockStatus(ProductVariant $variant): string
    {
        return $this->snapshot($variant)['status'];
    }

    public function isPurchasable(ProductVariant $variant): bool
    {
        return $this->availableQuantity($variant) > 0;
    }

    public function isLowStock(ProductVariant $variant): bool
    {
        return in_array($this->stockStatus($variant), ['low_stock', 'out_of_stock'], true)
            && $this->onHandQuantity($variant) > 0;
    }

    public function lowStockAlerts(int $limit = 10): Collection
    {
        return Inventory::query()
            ->with(['productVariant.product', 'warehouse'])
            ->lowStock()
            ->orderByRaw('quantity_on_hand - quantity_reserved ASC')
            ->limit($limit)
            ->get();
    }

    public function defaultWarehouse(): ?Warehouse
    {
        if ($this->defaultWarehouse !== null) {
            return $this->defaultWarehouse;
        }

        $this->defaultWarehouse = Warehouse::query()->where('is_default', true)->first()
            ?? Warehouse::query()->where('is_active', true)->first();

        return $this->defaultWarehouse;
    }

    public function inventoryItem(ProductVariant $variant, ?Warehouse $warehouse = null): ?Inventory
    {
        $warehouse ??= $this->defaultWarehouse();

        if (! $warehouse) {
            return null;
        }

        return $warehouse->inventoryItems()
            ->where('product_variant_id', $variant->id)
            ->first();
    }

    public function syncVariantAggregate(int $variantId): void
    {
        $total = (int) Inventory::query()
            ->where('product_variant_id', $variantId)
            ->sum('quantity_on_hand');

        ProductVariant::whereKey($variantId)->update(['stock_quantity' => $total]);
    }

    public function setOnHand(ProductVariant $variant, int $quantity, ?int $lowStockThreshold = null): void
    {
        $warehouse = $this->defaultWarehouse();

        if (! $warehouse) {
            $variant->update([
                'stock_quantity' => max(0, $quantity),
                'low_stock_threshold' => $lowStockThreshold ?? $variant->low_stock_threshold,
            ]);

            return;
        }

        $existing = $this->inventoryItem($variant, $warehouse);
        $reserved = $existing?->quantity_reserved ?? 0;

        if ($quantity < $reserved) {
            throw new \InvalidArgumentException('On-hand quantity cannot be less than reserved stock.');
        }

        $warehouse->inventoryItems()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            [
                'quantity_on_hand' => max(0, $quantity),
                'quantity_reserved' => $reserved,
                'low_stock_threshold' => $lowStockThreshold
                    ?? $existing?->low_stock_threshold
                    ?? $variant->low_stock_threshold
                    ?? config('shop.low_stock_threshold', 5),
            ],
        );

        $this->syncVariantAggregate($variant->id);
    }

    public function reserve(
        ProductVariant $variant,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): void {
        if ($quantity < 1) {
            return;
        }

        $warehouse = $this->defaultWarehouse();

        if (! $warehouse) {
            $this->assertVariantFallbackAvailable($variant, $quantity);

            return;
        }

        DB::transaction(function () use ($variant, $quantity, $referenceType, $referenceId, $notes, $warehouse): void {
            $item = $warehouse->inventoryItems()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw ValidationException::withMessages([
                    'quantity' => 'This item is out of stock.',
                ]);
            }

            $available = max(0, $item->quantity_on_hand - $item->quantity_reserved);

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$available} units available.",
                ]);
            }

            $item->update([
                'quantity_reserved' => $item->quantity_reserved + $quantity,
            ]);

            StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'movement_type' => StockMovementType::Reserved,
                'quantity' => $quantity,
                'balance_after' => $item->quantity_on_hand,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'performed_by' => Auth::id(),
            ]);
        });
    }

    public function release(
        ProductVariant $variant,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): void {
        if ($quantity < 1) {
            return;
        }

        $warehouse = $this->defaultWarehouse();

        if (! $warehouse) {
            return;
        }

        DB::transaction(function () use ($variant, $quantity, $referenceType, $referenceId, $notes, $warehouse): void {
            $item = $warehouse->inventoryItems()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                return;
            }

            $releaseQty = min($quantity, $item->quantity_reserved);
            if ($releaseQty < 1) {
                return;
            }

            $item->update([
                'quantity_reserved' => $item->quantity_reserved - $releaseQty,
            ]);

            StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
                'movement_type' => StockMovementType::Released,
                'quantity' => -$releaseQty,
                'balance_after' => $item->quantity_on_hand,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'performed_by' => Auth::id(),
            ]);
        });
    }

    public function commitSale(
        ProductVariant $variant,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): void {
        if ($quantity < 1) {
            return;
        }

        $warehouse = $this->defaultWarehouse();

        if ($warehouse) {
            DB::transaction(function () use ($variant, $quantity, $referenceType, $referenceId, $notes, $warehouse): void {
                $item = $warehouse->inventoryItems()
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();

                if ($item) {
                    if ($item->quantity_on_hand < $quantity) {
                        throw ValidationException::withMessages([
                            'cart' => "Only {$item->quantity_on_hand} units available in stock.",
                        ]);
                    }

                    $releaseQty = min($quantity, $item->quantity_reserved);
                    $newReserved = $item->quantity_reserved - $releaseQty;
                    $newOnHand = $item->quantity_on_hand - $quantity;

                    $item->update([
                        'quantity_reserved' => $newReserved,
                        'quantity_on_hand' => $newOnHand,
                    ]);

                    if ($releaseQty > 0) {
                        StockMovement::create([
                            'warehouse_id' => $warehouse->id,
                            'product_variant_id' => $variant->id,
                            'movement_type' => StockMovementType::Released,
                            'quantity' => -$releaseQty,
                            'balance_after' => $newOnHand,
                            'reference_type' => $referenceType,
                            'reference_id' => $referenceId,
                            'notes' => $notes ? "{$notes} (reservation released)" : 'Reservation released',
                            'performed_by' => Auth::id(),
                        ]);
                    }

                    StockMovement::create([
                        'warehouse_id' => $warehouse->id,
                        'product_variant_id' => $variant->id,
                        'movement_type' => StockMovementType::Sale,
                        'quantity' => -$quantity,
                        'balance_after' => $newOnHand,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                        'notes' => $notes,
                        'performed_by' => Auth::id(),
                    ]);

                    $this->syncVariantAggregate($variant->id);

                    return;
                }

                $this->commitSaleOnVariantOnly($variant, $quantity);
            });

            return;
        }

        $this->commitSaleOnVariantOnly($variant, $quantity);
    }

    private function commitSaleOnVariantOnly(ProductVariant $variant, int $quantity): void
    {
        $lockedVariant = ProductVariant::query()
            ->whereKey($variant->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($lockedVariant->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'cart' => "Only {$lockedVariant->stock_quantity} units available for {$lockedVariant->sku}.",
            ]);
        }

        $lockedVariant->decrement('stock_quantity', $quantity);
    }

    private function assertVariantFallbackAvailable(ProductVariant $variant, int $quantity): void
    {
        if ((int) $variant->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$variant->stock_quantity} units available.",
            ]);
        }
    }
}
