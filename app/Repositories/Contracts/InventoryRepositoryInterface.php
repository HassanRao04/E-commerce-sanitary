<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryRepositoryInterface extends RepositoryInterface
{
    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function lowStock(int $perPage = 15): LengthAwarePaginator;
}
