<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'email',
        'contact_phone',
        'whatsapp',
        'address',
        'default_meta_description',
        'currency',
        'tax_rate',
        'shipping_flat_rate',
        'social_links',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'shipping_flat_rate' => 'decimal:2',
            'social_links' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'site_name' => config('app.name'),
            'currency' => 'PKR',
            'tax_rate' => 0,
            'shipping_flat_rate' => 0,
        ]);
    }
}
