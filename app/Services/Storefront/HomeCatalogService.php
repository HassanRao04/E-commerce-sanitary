<?php

namespace App\Services\Storefront;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HomeCatalogService
{
    private const LIMIT = 12;

    /** @var array<int, string> */
    private const EAGER = ['brand', 'defaultVariant', 'images'];

    public function featured(): Collection
    {
        $products = $this->baseQuery()
            ->featured()
            ->latest()
            ->limit(self::LIMIT)
            ->get();

        return $this->ensureMinimum($products, fn (Builder $query) => $query->latest());
    }

    public function bestSelling(): Collection
    {
        $products = $this->baseQuery()
            ->bestSellers()
            ->latest()
            ->limit(self::LIMIT)
            ->get();

        return $this->ensureMinimum($products, fn (Builder $query) => $query->orderByDesc('is_best_seller')->latest());
    }

    public function newArrivals(): Collection
    {
        $products = $this->baseQuery()
            ->newArrivals()
            ->latest()
            ->limit(self::LIMIT)
            ->get();

        return $this->ensureMinimum($products, fn (Builder $query) => $query->latest());
    }

    public function trending(): Collection
    {
        $products = $this->baseQuery()
            ->withSum(['orderItems as recent_sold' => function ($query): void {
                $query->whereHas('order', fn ($order) => $order->where('created_at', '>=', now()->subDays(90)));
            }], 'quantity')
            ->orderByDesc('recent_sold')
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_featured')
            ->latest()
            ->limit(self::LIMIT)
            ->get();

        if ($products->where(fn (Product $product) => (int) ($product->recent_sold ?? 0) > 0)->count() >= 4) {
            return $products;
        }

        return $this->ensureMinimum(
            collect(),
            fn (Builder $query) => $query->featured()->orderByDesc('is_best_seller')->latest(),
        );
    }

    public function flashSale(): Collection
    {
        $products = $this->baseQuery()
            ->onSale()
            ->latest()
            ->limit(self::LIMIT)
            ->get();

        return $this->ensureMinimum($products, fn (Builder $query) => $query->onSale()->inRandomOrder());
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
    private function ensureMinimum(Collection $products, callable $fallback): Collection
    {
        if ($products->count() >= 4) {
            return $products;
        }

        $exclude = $products->pluck('id');

        $fill = $fallback($this->baseQuery())
            ->when($exclude->isNotEmpty(), fn (Builder $query) => $query->whereNotIn('products.id', $exclude))
            ->limit(self::LIMIT)
            ->get();

        return $products->concat($fill)->unique('id')->take(self::LIMIT)->values();
    }
}
