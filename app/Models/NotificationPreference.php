<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email_orders',
        'email_promotions',
        'sms_orders',
    ];

    protected function casts(): array
    {
        return [
            'email_orders' => 'boolean',
            'email_promotions' => 'boolean',
            'sms_orders' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
