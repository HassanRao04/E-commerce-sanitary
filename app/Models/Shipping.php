<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipping extends Model
{
    use NormalizesStrings;

    protected $table = 'shipments';

    protected $fillable = [
        'order_id',
        'courier_name',
        'tracking_number',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected function isDelivered(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === ShipmentStatus::Delivered || filled($this->delivered_at),
        );
    }

    protected function isInTransit(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array($this->status, [
                ShipmentStatus::InTransit,
                ShipmentStatus::OutForDelivery,
                ShipmentStatus::Picked,
            ], true),
        );
    }

    protected function trackingUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (blank($this->tracking_number) || blank($this->courier_name)) {
                    return null;
                }

                $courier = str($this->courier_name)->lower()->value();
                $tracking = urlencode($this->tracking_number);

                return match (true) {
                    str_contains($courier, 'tcs') => "https://www.tcsexpress.com/track/{$tracking}",
                    str_contains($courier, 'leopard') || str_contains($courier, 'lcs') => "https://www.leopardscourier.com/track/{$tracking}",
                    str_contains($courier, 'post') => "https://www.pakpost.gov.pk/track/{$tracking}",
                    default => null,
                };
            },
        );
    }

    protected function courierName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    protected function trackingNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeUpper($value),
        );
    }

    #[Scope]
    protected function inTransit(Builder $query): void
    {
        $query->whereIn('status', [
            ShipmentStatus::Picked,
            ShipmentStatus::InTransit,
            ShipmentStatus::OutForDelivery,
        ]);
    }

    #[Scope]
    protected function delivered(Builder $query): void
    {
        $query->where('status', ShipmentStatus::Delivered);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', ShipmentStatus::Pending);
    }

    #[Scope]
    protected function withTrackingNumber(Builder $query, string $trackingNumber): void
    {
        $query->where('tracking_number', static::normalizeUpper($trackingNumber));
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('tracking_number', 'like', "%{$term}%")
                ->orWhere('courier_name', 'like', "%{$term}%")
                ->orWhereHas('order', fn (Builder $orderQuery) => $orderQuery->search($term));
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(Tracking::class, 'shipment_id');
    }

    public function latestTrackingEvent(): HasOne
    {
        return $this->hasOne(Tracking::class, 'shipment_id')->latestOfMany('event_at');
    }

    /** @deprecated Use trackingEvents() */
    public function events(): HasMany
    {
        return $this->trackingEvents();
    }
}
