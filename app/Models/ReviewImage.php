<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReviewImage extends Model
{
    protected $fillable = [
        'review_id',
        'path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Storage::disk('public')->url($this->path),
        );
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
