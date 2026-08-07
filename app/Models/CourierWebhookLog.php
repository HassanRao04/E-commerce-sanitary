<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierWebhookLog extends Model
{
    protected $fillable = [
        'courier_provider_id',
        'provider_slug',
        'event_type',
        'payload',
        'processed',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
        ];
    }

    public function courierProvider(): BelongsTo
    {
        return $this->belongsTo(CourierProvider::class);
    }
}
