<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findBySlug(string $slug): ?Product;

    public function activeWithRelations(): Collection;
}
