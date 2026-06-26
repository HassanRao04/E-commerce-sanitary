<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Product);
    }

    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['brand', 'defaultVariant', 'variants:id,product_id,stock_quantity', 'images' => fn ($q) => $q->where('is_primary', true)])
            ->withCount('variants');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('base_sku', 'like', "%{$term}%");
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($brandId = $filters['brand_id'] ?? null) {
            $query->where('brand_id', $brandId);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::query()->where('slug', $slug)->first();
    }

    public function activeWithRelations(): Collection
    {
        return Product::query()
            ->active()
            ->with(['brand', 'defaultVariant', 'images'])
            ->latest()
            ->get();
    }
}
