<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use NormalizesStrings;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'order_item_id',
        'rating',
        'title',
        'body',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'is_featured' => 'boolean',
        ];
    }

    protected function stars(): Attribute
    {
        return Attribute::make(
            get: fn (): string => str_repeat('★', $this->rating).str_repeat('☆', 5 - $this->rating),
        );
    }

    protected function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === ReviewStatus::Approved,
        );
    }

    protected function isPending(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === ReviewStatus::Pending,
        );
    }

    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->body ? str($this->body)->limit(120)->value() : null,
        );
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    protected function rating(): Attribute
    {
        return Attribute::make(
            set: fn (?int $value): ?int => $value === null ? null : max(1, min(5, $value)),
        );
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', ReviewStatus::Approved);
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where('status', ReviewStatus::Approved);
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', ReviewStatus::Pending);
    }

    #[Scope]
    protected function forProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    #[Scope]
    protected function withRating(Builder $query, int $rating): void
    {
        $query->where('rating', $rating);
    }

    #[Scope]
    protected function minimumRating(Builder $query, int $rating): void
    {
        $query->where('rating', '>=', $rating);
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('title', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->search($term));
        });
    }

    #[Scope]
    protected function recent(Builder $query): void
    {
        $query->latest('created_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order');
    }
}
