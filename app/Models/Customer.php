<?php

namespace App\Models;

use App\Enums\CustomerType;
use Database\Factories\CustomerFactory;
use App\Models\Concerns\FormatsMoney;
use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use FormatsMoney;
    use HasFactory;
    use NormalizesStrings;
    use SoftDeletes;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'user_id',
        'company_name',
        'tax_number',
        'customer_type',
        'credit_limit',
        'lifetime_spend',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'credit_limit' => 'decimal:2',
            'lifetime_spend' => 'decimal:2',
        ];
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->company_name ?: $this->user?->name ?? 'Guest',
        );
    }

    protected function formattedLifetimeSpend(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->lifetime_spend),
        );
    }

    protected function hasCreditAvailable(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (float) $this->credit_limit > (float) $this->lifetime_spend,
        );
    }

    protected function formattedCreditLimit(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->formatMoneyAttribute($this->credit_limit),
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => ucfirst($this->customer_type?->value ?? 'retail'),
        );
    }

    protected function companyName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    protected function taxNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeUpper($value),
        );
    }

    #[Scope]
    protected function retail(Builder $query): void
    {
        $query->where('customer_type', CustomerType::Retail);
    }

    #[Scope]
    protected function wholesale(Builder $query): void
    {
        $query->where('customer_type', CustomerType::Wholesale);
    }

    #[Scope]
    protected function dealer(Builder $query): void
    {
        $query->where('customer_type', CustomerType::Dealer);
    }

    #[Scope]
    protected function withSpendAbove(Builder $query, float $amount): void
    {
        $query->where('lifetime_spend', '>=', $amount);
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('company_name', 'like', "%{$term}%")
                ->orWhere('tax_number', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->search($term));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }
}
