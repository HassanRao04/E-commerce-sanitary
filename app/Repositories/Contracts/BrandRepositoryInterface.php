<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BrandRepositoryInterface extends RepositoryInterface
{
    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function activeList(): \Illuminate\Database\Eloquent\Collection;
}
