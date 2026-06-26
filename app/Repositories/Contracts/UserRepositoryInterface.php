<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findByEmail(string $email): ?User;
}
