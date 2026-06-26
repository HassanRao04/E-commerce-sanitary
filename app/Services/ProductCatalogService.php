<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductCatalogService
{
    public function baseQuery(): Builder
    {
        return Product::query()
            ->active()
            ->with(['brand', 'defaultVariant', 'images' => fn ($q) => $q->where('is_primary', true)]);
    }

    public function applyFilters(Builder $query, Request $request): Builder
    {
        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('base_sku', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($brandId = $request->integer('brand')) {
            $query->where('brand_id', $brandId);
        } elseif ($request->filled('brands')) {
            $brandIds = collect((array) $request->input('brands'))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            if ($brandIds !== []) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        if ($categorySlug = $request->string('category')->trim()->toString()) {
            $category = Category::query()->where('slug', $categorySlug)->first();

            if ($category) {
                $query->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id));
            }
        } elseif ($request->filled('categories')) {
            $categorySlugs = collect((array) $request->input('categories'))
                ->map(fn ($slug) => trim((string) $slug))
                ->filter()
                ->values()
                ->all();

            if ($categorySlugs !== []) {
                $categoryIds = Category::query()->whereIn('slug', $categorySlugs)->pluck('id');

                if ($categoryIds->isNotEmpty()) {
                    $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
                }
            }
        }

        if ($request->filled('min_price')) {
            $query->whereHas('defaultVariant', fn ($q) => $q->where('price', '>=', (float) $request->input('min_price')));
        }

        if ($request->filled('max_price')) {
            $query->whereHas('defaultVariant', fn ($q) => $q->where('price', '<=', (float) $request->input('max_price')));
        }

        match ($request->string('collection')->trim()->toString()) {
            'new' => $query->where('is_new_arrival', true),
            'best-sellers' => $query->where('is_best_seller', true),
            'featured' => $query->where('is_featured', true),
            'trending' => $query->where(function (Builder $builder): void {
                $builder->where('is_best_seller', true)
                    ->orWhere('is_featured', true);
            }),
            'sale' => $query->whereHas('variants', fn ($q) => $q->active()->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')),
            'seasonal' => $query->where('is_featured', true),
            default => null,
        };

        return $this->applySort($query, $request->string('sort')->toString());
    }

    public function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'price_asc', 'price_desc' => $query
                ->leftJoin('product_variants as sort_variants', function ($join): void {
                    $join->on('products.id', '=', 'sort_variants.product_id')
                        ->where('sort_variants.is_default', true);
                })
                ->select('products.*')
                ->orderBy('sort_variants.price', $sort === 'price_asc' ? 'asc' : 'desc'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };
    }

    public function paginate(Builder $query, Request $request, int $perPage = 12): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    /** @return array{min: int, max: int} */
    public function priceRange(): array
    {
        $range = Product::query()
            ->active()
            ->join('product_variants as price_variants', function ($join): void {
                $join->on('products.id', '=', 'price_variants.product_id')
                    ->where('price_variants.is_default', true)
                    ->where('price_variants.is_active', true);
            })
            ->selectRaw('MIN(price_variants.price) as min_price, MAX(price_variants.price) as max_price')
            ->first();

        $min = (int) floor((float) ($range->min_price ?? 0));
        $max = (int) ceil((float) ($range->max_price ?? 0));

        if ($max <= $min) {
            $max = $min + 1000;
        }

        return ['min' => $min, 'max' => $max];
    }

    /** @return array{brands: \Illuminate\Support\Collection, categories: \Illuminate\Support\Collection} */
    public function filterOptions(): array
    {
        return [
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ];
    }
}
