<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Support\HomepageSections;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HomeCatalogService
{
    /** @var array<int, string> */
    private const EAGER = ['brand', 'defaultVariant', 'images', 'variants'];

    /**
     * @param  array<string, mixed>  $config
     */
    public function forSection(string $key, array $config): Collection
    {
        $limit = max(1, min(24, (int) ($config['limit'] ?? 12)));

        if (($config['mode'] ?? 'auto') === 'manual') {
            $manual = $this->manualProducts($config['product_ids'] ?? [], $limit);

            if ($manual->isNotEmpty()) {
                return $manual;
            }
        }

        return match ($key) {
            HomepageSections::FEATURED => $this->featured($limit),
            HomepageSections::BEST_SELLERS => $this->bestSelling($limit),
            HomepageSections::NEW_ARRIVALS => $this->newArrivals($limit),
            HomepageSections::TRENDING => $this->trending($limit),
            HomepageSections::FLASH_SALE => $this->flashSale($limit),
            default => collect(),
        };
    }

    public function featured(int $limit = 12): Collection
    {
        $products = $this->baseQuery()
            ->featured()
            ->latest()
            ->limit($limit)
            ->get();

        return $this->ensureMinimum($products, $limit, fn (Builder $query) => $query->latest());
    }

    public function bestSelling(int $limit = 12): Collection
    {
        $products = $this->baseQuery()
            ->bestSellers()
            ->latest()
            ->limit($limit)
            ->get();

        return $this->ensureMinimum($products, $limit, fn (Builder $query) => $query->orderByDesc('is_best_seller')->latest());
    }

    public function newArrivals(int $limit = 12): Collection
    {
        $products = $this->baseQuery()
            ->newArrivals()
            ->latest()
            ->limit($limit)
            ->get();

        return $this->ensureMinimum($products, $limit, fn (Builder $query) => $query->latest());
    }

    public function trending(int $limit = 12): Collection
    {
        $products = $this->baseQuery()
            ->withSum(['orderItems as recent_sold' => function ($query): void {
                $query->whereHas('order', fn ($order) => $order->where('created_at', '>=', now()->subDays(90)));
            }], 'quantity')
            ->orderByDesc('recent_sold')
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get();

        if ($products->where(fn (Product $product) => (int) ($product->recent_sold ?? 0) > 0)->count() >= 4) {
            return $products;
        }

        return $this->ensureMinimum(
            collect(),
            $limit,
            fn (Builder $query) => $query->featured()->orderByDesc('is_best_seller')->latest(),
        );
    }

    public function flashSale(int $limit = 12): Collection
    {
        $products = $this->baseQuery()
            ->onSale()
            ->latest()
            ->limit($limit)
            ->get();

        return $this->ensureMinimum($products, $limit, fn (Builder $query) => $query->onSale()->inRandomOrder());
    }

    /**
     * @param  list<int>  $productIds
     */
    private function manualProducts(array $productIds, int $limit): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        $products = $this->baseQuery()
            ->whereIn('products.id', $productIds)
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $productIds, true))
            ->values()
            ->take($limit);

        return $products;
    }

    private function baseQuery(): Builder
    {
        return Product::query()
            ->active()
            ->inStock()
            ->with(self::EAGER)
            ->withAvg(['reviews as average_rating' => fn ($query) => $query->approved()], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($query) => $query->approved()]);
    }

    /** @param callable(Builder): Builder $fallback */
    private function ensureMinimum(Collection $products, int $limit, callable $fallback): Collection
    {
        if ($products->count() >= 4) {
            return $products;
        }

        $exclude = $products->pluck('id');

        $fill = $fallback($this->baseQuery())
            ->when($exclude->isNotEmpty(), fn (Builder $query) => $query->whereNotIn('products.id', $exclude))
            ->limit($limit)
            ->get();

        return $products->concat($fill)->unique('id')->take($limit)->values();
    }
}
