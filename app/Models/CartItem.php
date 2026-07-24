<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'product_offer_id',
        'pipe_length_option_id',
        'quantity',
        'unit_price',
        'variant_options',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'variant_options' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function productOffer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class);
    }

    public function pipeLengthOption(): BelongsTo
    {
        return $this->belongsTo(ProductPipeLengthOption::class, 'pipe_length_option_id');
    }
}
