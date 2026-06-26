<?php

namespace App\Repositories\Eloquent;

use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryRepository extends BaseRepository implements InventoryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Inventory);
    }

    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Inventory::query()
            ->with(['warehouse', 'productVariant.product']);

        if ($warehouseId = $filters['warehouse_id'] ?? null) {
            $query->forWarehouse((int) $warehouseId);
        }

        if (($filters['low_stock'] ?? false) === '1' || ($filters['low_stock'] ?? false) === true) {
            $query->lowStock();
        }

        if ($term) {
            $query->whereHas('productVariant', function ($q) use ($term) {
                $q->where('sku', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$term}%"));
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function lowStock(int $perPage = 15): LengthAwarePaginator
    {
        return Inventory::query()
            ->lowStock()
            ->with(['warehouse', 'productVariant.product'])
            ->paginate($perPage);
    }
}
