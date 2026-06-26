<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'product_variant_id',
    ];

    protected function hasVariant(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => filled($this->product_variant_id),
        );
    }

    protected function isGuest(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => blank($this->user_id) && filled($this->session_id),
        );
    }

    #[Scope]
    protected function forUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    #[Scope]
    protected function forSession(Builder $query, string $sessionId): void
    {
        $query->where('session_id', $sessionId);
    }

    #[Scope]
    protected function forProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    #[Scope]
    protected function guest(Builder $query): void
    {
        $query->whereNull('user_id')->whereNotNull('session_id');
    }

    #[Scope]
    protected function authenticated(Builder $query): void
    {
        $query->whereNotNull('user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
