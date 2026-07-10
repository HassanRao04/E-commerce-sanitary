<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use GeneratesSlug;
    use NodeTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'banner_image',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->image ? Storage::url($this->image) : null,
        );
    }

    protected function bannerUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->banner_image ? Storage::url($this->banner_image) : null,
        );
    }

    protected function displayImageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->image) {
                    return Storage::url($this->image);
                }

                if ($this->banner_image) {
                    return Storage::url($this->banner_image);
                }

                $categoryIds = $this->descendants()->pluck('id')->push($this->id);

                $product = Product::query()
                    ->active()
                    ->whereHas('categories', fn (Builder $query) => $query->whereIn('categories.id', $categoryIds))
                    ->with(['images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
                    ->first();

                return $product?->primary_image_url ?? route('media.placeholder');
            },
        );
    }

    protected function isRoot(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->parent_id === null,
        );
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('name', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%");
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
