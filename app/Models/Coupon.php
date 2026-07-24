<?php

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use App\Models\Concerns\FormatsMoney;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use FormatsMoney;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'influencer_id',
        'commission_enabled',
        'commission_type',
        'commission_value',
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'commission_enabled' => 'boolean',
            'commission_type' => CouponType::class,
            'commission_value' => 'decimal:2',
        ];
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => filled($this->expires_at) && $this->expires_at->isPast(),
        );
    }

    protected function isExhausted(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => filled($this->max_uses) && $this->used_count >= $this->max_uses,
        );
    }

    protected function isValid(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->is_active
                && ! $this->is_expired
                && ! $this->is_exhausted
                && (blank($this->starts_at) || $this->starts_at->isPast()),
        );
    }

    protected function formattedValue(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->type === CouponType::Percent
                ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'%'
                : $this->formatMoneyAttribute($this->value),
        );
    }

    protected function remainingUses(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => filled($this->max_uses)
                ? max(0, (int) $this->max_uses - (int) $this->used_count)
                : null,
        );
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value ? Str::upper(trim($value)) : null,
        );
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function valid(Builder $query): void
    {
        $query->active()
            ->where(function (Builder $builder): void {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('max_uses')
                    ->orWhereColumn('used_count', '<', 'max_uses');
            });
    }

    #[Scope]
    protected function byCode(Builder $query, string $code): void
    {
        $query->where('code', Str::upper(trim($code)));
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    public function trackedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->min_order_amount && $subtotal < (float) $this->min_order_amount) {
            return 0.0;
        }

        return match ($this->type) {
            CouponType::Fixed => min((float) $this->value, $subtotal),
            CouponType::Percent => round($subtotal * ((float) $this->value / 100), 2),
        };
    }

    public function calculateCommission(float $baseAmount): float
    {
        if (
            ! $this->commission_enabled
            || ! $this->influencer_id
            || ! $this->commission_type
            || $this->commission_value === null
        ) {
            return 0.0;
        }

        return match ($this->commission_type) {
            CouponType::Fixed => min((float) $this->commission_value, $baseAmount),
            CouponType::Percent => round($baseAmount * ((float) $this->commission_value / 100), 2),
        };
    }
}
