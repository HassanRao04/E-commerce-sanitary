<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Category);
    }

    public function tree(): Collection
    {
        return Category::query()
            ->defaultOrder()
            ->with('children')
            ->get()
            ->toTree();
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::query()->where('slug', $slug)->first();
    }

    public function paginatedList(?string $term = null, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Category::query()->with('parent')->withCount('products');

        if ($term) {
            $query->search($term);
        }

        return $query->defaultOrder()->paginate($perPage)->withQueryString();
    }
}
