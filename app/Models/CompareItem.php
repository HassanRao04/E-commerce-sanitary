<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompareItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'compare_list_id',
        'product_id',
        'product_variant_id',
    ];

    public function compareList(): BelongsTo
    {
        return $this->belongsTo(CompareList::class);
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
