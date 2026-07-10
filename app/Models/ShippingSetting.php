<?php

namespace App\Models;

use App\Enums\ShippingMethod;
use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    protected $fillable = [
        'flat_rate_enabled',
        'flat_rate_amount',
        'product_rate_enabled',
        'category_rate_enabled',
        'free_shipping_enabled',
        'free_shipping_threshold',
        'default_method',
    ];

    protected function casts(): array
    {
        return [
            'flat_rate_enabled' => 'boolean',
            'flat_rate_amount' => 'decimal:2',
            'product_rate_enabled' => 'boolean',
            'category_rate_enabled' => 'boolean',
            'free_shipping_enabled' => 'boolean',
            'free_shipping_threshold' => 'decimal:2',
            'default_method' => ShippingMethod::class,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'flat_rate_enabled' => true,
            'flat_rate_amount' => (float) config('shop.shipping_flat_rate', 0),
            'product_rate_enabled' => false,
            'category_rate_enabled' => false,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => (float) config('shop.free_shipping_threshold', 0),
            'default_method' => ShippingMethod::Flat,
        ]);
    }
}
