<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_filterable',
        'is_variant_attribute',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_variant_attribute' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->values();
    }

    public function isColorAttribute(): bool
    {
        return $this->type === 'color' || $this->slug === 'color';
    }
}
