<?php

namespace App\Models;

use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tracking extends Model
{
    use NormalizesStrings;

    protected $table = 'shipment_tracking_events';

    protected $fillable = [
        'shipment_id',
        'status',
        'location',
        'description',
        'event_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
        ];
    }

    protected function isManual(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->source === 'manual',
        );
    }

    protected function summary(): Attribute
    {
        return Attribute::make(
            get: fn (): string => collect([$this->status, $this->location, $this->description])
                ->filter()
                ->implode(' — '),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    protected function location(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('event_at');
    }

    #[Scope]
    protected function chronological(Builder $query): void
    {
        $query->orderBy('event_at');
    }

    #[Scope]
    protected function fromSource(Builder $query, string $source): void
    {
        $query->where('source', $source);
    }

    #[Scope]
    protected function forShipment(Builder $query, int $shipmentId): void
    {
        $query->where('shipment_id', $shipmentId);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipping::class, 'shipment_id');
    }

    /** @deprecated Use shipment() */
    public function shipping(): BelongsTo
    {
        return $this->shipment();
    }
}
