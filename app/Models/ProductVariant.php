<?php

namespace App\Models;

use App\Models\Product;
use App\Services\InventoryControlService;
use App\Support\VariantColorSwatch;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'variant_name',
        'finish',
        'color_name',
        'color_hex',
        'size',
        'material',
        'price',
        'sale_price',
        'wholesale_price',
        'dealer_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'weight',
        'length',
        'width',
        'height',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'dealer_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isInStock(): bool
    {
        return app(InventoryControlService::class)->isPurchasable($this);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function (Builder $stockQuery): void {
            $stockQuery->whereHas('inventory', fn (Builder $inventoryQuery) => $inventoryQuery
                ->whereRaw('quantity_on_hand - quantity_reserved > 0'))
                ->orWhere(function (Builder $fallbackQuery): void {
                    $fallbackQuery->whereDoesntHave('inventory')
                        ->where('stock_quantity', '>', 0);
                });
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }

    /** @deprecated Use inventory() */
    public function inventoryItems(): HasMany
    {
        return $this->inventory();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return array{name: string, hex: string}|null
     */
    public function swatchColor(): ?array
    {
        return VariantColorSwatch::forVariant($this);
    }

    public function swatchHex(): ?string
    {
        $swatch = VariantColorSwatch::forVariant($this);

        return $swatch['hex'] ?? VariantColorSwatch::normalizeHex($this->color_hex);
    }
}
