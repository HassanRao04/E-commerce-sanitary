<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use App\Models\Concerns\FormatsMoney;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use FormatsMoney;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'tracking_token',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'billing_address_id',
        'shipping_address_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'discount_total',
        'shipping_total',
        'tax_total',
        'grand_total',
        'coupon_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    protected function formattedGrandTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->grand_total),
        );
    }

    protected function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->subtotal),
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->payment_status === PaymentStatus::Paid,
        );
    }

    protected function isCancelled(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === OrderStatus::Cancelled,
        );
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === OrderStatus::Delivered,
        );
    }

    protected function itemCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->items()->sum('quantity'),
        );
    }

    protected function estimatedDeliveryDate(): Attribute
    {
        return Attribute::make(
            get: fn (): ?\Illuminate\Support\Carbon => $this->created_at?->copy()->addWeekdays(
                (int) config('shop.estimated_delivery_days', 5),
            ),
        );
    }

    protected function trackingNumber(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->relationLoaded('shipments')) {
                    return $this->shipments->first()?->tracking_number;
                }

                return $this->shipments()->value('tracking_number');
            },
        );
    }

    protected function orderNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value ? Str::upper($value) : null,
        );
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    #[Scope]
    protected function paid(Builder $query): void
    {
        $query->where('payment_status', PaymentStatus::Paid);
    }

    #[Scope]
    protected function forCustomer(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    #[Scope]
    protected function withStatus(Builder $query, OrderStatus $status): void
    {
        $query->where('status', $status);
    }

    #[Scope]
    protected function recent(Builder $query): void
    {
        $query->latest('created_at');
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('order_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_email', 'like', "%{$term}%");
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id', 'user_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipping::class);
    }

    /** @deprecated Use shipments() */
    public function shipping(): HasMany
    {
        return $this->shipments();
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
