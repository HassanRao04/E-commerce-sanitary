<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOffer extends Model
{
    protected $fillable = [
        'product_id',
        'buy_quantity',
        'discount_percent',
        'free_shipping',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'buy_quantity' => 'integer',
            'discount_percent' => 'decimal:2',
            'free_shipping' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
