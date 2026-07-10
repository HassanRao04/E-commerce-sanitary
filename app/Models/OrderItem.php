<?php

namespace App\Models;

use App\Models\Concerns\FormatsMoney;
use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use FormatsMoney;
    use NormalizesStrings;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'variant_options',
        'sku',
        'quantity',
        'unit_price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'variant_options' => 'array',
        ];
    }

    protected function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->total),
        );
    }

    protected function formattedUnitPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->unit_price),
        );
    }

    protected function lineTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute((string) ((float) $this->unit_price * $this->quantity)),
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim($this->product_name.' '.($this->variant_name ?? '')),
        );
    }

    protected function sku(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeUpper($value),
        );
    }

    #[Scope]
    protected function forOrder(Builder $query, int $orderId): void
    {
        $query->where('order_id', $orderId);
    }

    #[Scope]
    protected function forProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    #[Scope]
    protected function withSku(Builder $query, string $sku): void
    {
        $query->where('sku', Str::upper(trim($sku)));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
