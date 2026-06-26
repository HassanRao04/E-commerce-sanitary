<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findByOrderNumber(string $orderNumber): ?Order;
}
