<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Services\ProductPricingService;
use Database\Factories\ProductFactory;
use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use GeneratesSlug;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'short_description',
        'description',
        'product_type',
        'base_sku',
        'status',
        'installation_type',
        'material',
        'warranty_text',
        'is_featured',
        'is_new_arrival',
        'is_best_seller',
        'is_project_suitable',
        'default_variant_id',
        'meta_title',
        'meta_description',
        'canonical_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_project_suitable' => 'boolean',
        ];
    }

    protected function primaryImageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->relationLoaded('images')) {
                    $image = $this->images->firstWhere('is_primary', true)
                        ?? $this->images->sortBy('sort_order')->first();
                } else {
                    $image = $this->images()->where('is_primary', true)->first()
                        ?? $this->images()->orderBy('sort_order')->first();
                }

                return $image?->url ?? route('media.placeholder');
            },
        );
    }

    protected function averageRating(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round((float) $this->reviews()->approved()->avg('rating'), 1),
        );
    }

    protected function isPurchasable(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === ProductStatus::Active
                && $this->variants()->active()->inStock()->exists(),
        );
    }

    protected function priceFrom(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                $pricing = app(ProductPricingService::class);

                $variants = $this->relationLoaded('variants')
                    ? $this->variants->where('is_active', true)
                    : $this->variants()->active()->get();

                if ($variants->isEmpty()) {
                    return null;
                }

                return $variants->min(fn (ProductVariant $variant): float => $pricing->displayPrice($variant));
            },
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => filled($value) ? trim($value) : null,
        );
    }

    protected function baseSku(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value ? Str::upper($value) : null,
        );
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ProductStatus::Active);
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    #[Scope]
    protected function newArrivals(Builder $query): void
    {
        $query->where('is_new_arrival', true);
    }

    #[Scope]
    protected function bestSellers(Builder $query): void
    {
        $query->where('is_best_seller', true);
    }

    #[Scope]
    protected function onSale(Builder $query): void
    {
        $query->whereHas('variants', function (Builder $variantQuery): void {
            $variantQuery->active()
                ->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'price');
        });
    }

    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->whereHas('variants', fn (Builder $variantQuery) => $variantQuery->active()->inStock());
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('name', 'like', "%{$term}%")
                ->orWhere('base_sku', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%");
        });
    }

    #[Scope]
    protected function forBrand(Builder $query, int $brandId): void
    {
        $query->where('brand_id', $brandId);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'product_collections');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'default_variant_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'related_products',
            'product_id',
            'related_product_id'
        );
    }

    public function frequentlyBoughtTogether(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'frequently_bought_together',
            'product_id',
            'related_product_id'
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'model');
    }
}
