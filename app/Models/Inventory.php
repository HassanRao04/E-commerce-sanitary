<?php

namespace App\Models;

use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Inventory extends Model
{
    use NormalizesStrings;

    protected $table = 'inventory_items';

    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'quantity_on_hand',
        'quantity_reserved',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    protected function availableQuantity(): Attribute
    {
        return Attribute::make(
            get: fn (): int => max(0, $this->quantity_on_hand - $this->quantity_reserved),
        );
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->available_quantity <= $this->low_stock_threshold,
        );
    }

    protected function isOutOfStock(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->available_quantity <= 0,
        );
    }

    protected function stockStatus(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match (true) {
                $this->is_out_of_stock => 'out_of_stock',
                $this->is_low_stock => 'low_stock',
                default => 'in_stock',
            },
        );
    }

    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->whereRaw('quantity_on_hand - quantity_reserved > 0');
    }

    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereRaw('quantity_on_hand - quantity_reserved <= low_stock_threshold');
    }

    #[Scope]
    protected function outOfStock(Builder $query): void
    {
        $query->whereRaw('quantity_on_hand - quantity_reserved <= 0');
    }

    #[Scope]
    protected function forWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }

    #[Scope]
    protected function forVariant(Builder $query, int $variantId): void
    {
        $query->where('product_variant_id', $variantId);
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->whereHas('productVariant', function (Builder $variantQuery) use ($term): void {
            $variantQuery->where('sku', 'like', "%{$term}%")
                ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->search($term));
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(
            Product::class,
            ProductVariant::class,
            'id',
            'id',
            'product_variant_id',
            'product_id',
        );
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id', 'product_variant_id')
            ->whereColumn('stock_movements.warehouse_id', 'inventory_items.warehouse_id');
    }
}
