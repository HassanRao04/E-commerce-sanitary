<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'badge_color',
        'sort_order',
        'is_system',
        'is_default',
        'is_cancellation',
        'is_return',
        'is_delivered',
        'is_terminal',
        'show_in_progress',
        'customer_group',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
            'is_cancellation' => 'boolean',
            'is_return' => 'boolean',
            'is_delivered' => 'boolean',
            'is_terminal' => 'boolean',
            'show_in_progress' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    #[Scope]
    protected function forProgress(Builder $query): void
    {
        $query->where('show_in_progress', true)->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'status', 'slug');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'status', 'slug');
    }
}
