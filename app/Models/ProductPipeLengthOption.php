<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPipeLengthOption extends Model
{
    protected $fillable = [
        'product_id',
        'label',
        'additional_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
