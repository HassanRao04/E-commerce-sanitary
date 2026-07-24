<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BestSellerProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::query()->update(['is_best_seller' => false]);

        $selectedIds = collect();

        // Prefer a different in-stock product per category than the featured pick.
        Category::query()
            ->whereNotNull('parent_id')
            ->whereHas('products', fn ($query) => $query->active()->inStock())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->each(function (Category $category) use ($selectedIds): void {
                if ($selectedIds->count() >= 20) {
                    return;
                }

                $productId = $category->products()
                    ->active()
                    ->inStock()
                    ->orderByDesc('products.id')
                    ->value('products.id');

                if ($productId) {
                    $selectedIds->push($productId);
                }
            });

        if ($selectedIds->count() < 20) {
            $fillIds = Product::query()
                ->active()
                ->inStock()
                ->whereNotIn('id', $selectedIds)
                ->orderByDesc('is_featured')
                ->orderBy('id')
                ->limit(20 - $selectedIds->count())
                ->pluck('id');

            $selectedIds = $selectedIds->merge($fillIds);
        }

        $selectedIds = $selectedIds->unique()->take(20)->values();

        if ($selectedIds->isEmpty()) {
            return;
        }

        Product::query()
            ->whereIn('id', $selectedIds)
            ->update(['is_best_seller' => true]);
    }
}
