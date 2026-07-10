<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewSetting extends Model
{
    protected $fillable = [
        'reviews_enabled',
        'auto_approve',
        'show_on_homepage',
        'max_featured',
        'homepage_mode',
    ];

    protected function casts(): array
    {
        return [
            'reviews_enabled' => 'boolean',
            'auto_approve' => 'boolean',
            'show_on_homepage' => 'boolean',
            'max_featured' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'reviews_enabled' => true,
            'auto_approve' => false,
            'show_on_homepage' => true,
            'max_featured' => 6,
            'homepage_mode' => 'featured',
        ]);
    }
}
