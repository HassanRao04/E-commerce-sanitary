<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function tree(): Collection;

    public function findBySlug(string $slug): ?Category;

    public function paginatedList(?string $term = null, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
