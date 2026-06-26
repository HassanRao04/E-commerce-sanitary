<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Services\Admin\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Inventory::class);

        return view('admin.inventory.index', [
            'items' => $this->inventoryService->paginatedList($request->only('q', 'warehouse_id', 'low_stock')),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(),
            'movementTypes' => StockMovementType::cases(),
        ]);
    }

    public function show(Inventory $inventory): View
    {
        $this->authorize('view', $inventory);

        $inventory->load(['warehouse', 'productVariant.product']);

        return view('admin.inventory.show', [
            'item' => $inventory,
            'movements' => $inventory->productVariant
                ->stockMovements()
                ->where('warehouse_id', $inventory->warehouse_id)
                ->latest('created_at')
                ->limit(20)
                ->get(),
            'movementTypes' => StockMovementType::cases(),
        ]);
    }

    public function adjust(AdjustInventoryRequest $request, Inventory $inventory): RedirectResponse
    {
        $this->authorize('update', $inventory);

        try {
            $this->inventoryService->adjust(
                $inventory,
                $request->enum('movement_type', StockMovementType::class),
                (int) $request->integer('quantity'),
                $request->input('notes'),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', 'Stock adjusted successfully.');
    }
}
