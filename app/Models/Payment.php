<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\FormatsMoney;
use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use FormatsMoney;
    use NormalizesStrings;

    protected $fillable = [
        'order_id',
        'gateway',
        'transaction_id',
        'gateway_reference',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'gateway' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->amount),
        );
    }

    protected function isSuccessful(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === PaymentStatus::Paid,
        );
    }

    protected function isPending(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === PaymentStatus::Pending,
        );
    }

    protected function isFailed(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === PaymentStatus::Failed,
        );
    }

    protected function currency(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeUpper($value),
        );
    }

    protected function transactionId(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    #[Scope]
    protected function successful(Builder $query): void
    {
        $query->where('status', PaymentStatus::Paid);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', PaymentStatus::Pending);
    }

    #[Scope]
    protected function failed(Builder $query): void
    {
        $query->where('status', PaymentStatus::Failed);
    }

    #[Scope]
    protected function forGateway(Builder $query, PaymentMethod $gateway): void
    {
        $query->where('gateway', $gateway);
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
            $builder->where('transaction_id', 'like', "%{$term}%")
                ->orWhere('gateway_reference', 'like', "%{$term}%")
                ->orWhereHas('order', fn (Builder $orderQuery) => $orderQuery->search($term));
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
