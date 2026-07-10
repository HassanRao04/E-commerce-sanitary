<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutRulesSetting extends Model
{
    protected $fillable = [
        'minimum_order_enabled',
        'minimum_order_amount',
        'coupons_enabled',
    ];

    protected function casts(): array
    {
        return [
            'minimum_order_enabled' => 'boolean',
            'minimum_order_amount' => 'decimal:2',
            'coupons_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'minimum_order_enabled' => false,
            'minimum_order_amount' => 0,
            'coupons_enabled' => true,
        ]);
    }
}
