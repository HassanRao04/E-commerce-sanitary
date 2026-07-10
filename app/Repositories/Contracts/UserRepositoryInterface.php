<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{
     *     name?: string|null,
     *     email?: string|null,
     *     role?: string|null,
     *     status?: string|null,
     *     staff_only?: bool,
     *     sort?: string|null,
     *     direction?: string|null,
     * }  $filters
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findByEmail(string $email): ?User;
}
