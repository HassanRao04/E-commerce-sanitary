<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    private const ALLOWED_SORTS = ['created_at', 'last_login_at'];

    public function __construct()
    {
        parent::__construct(new User);
    }

    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        if ($name = $filters['name'] ?? null) {
            $query->where(function ($builder) use ($name): void {
                $builder->where('name', 'like', "%{$name}%")
                    ->orWhere('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%");
            });
        }

        if ($email = $filters['email'] ?? null) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($role = $filters['role'] ?? null) {
            $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role));
        } elseif ($filters['staff_only'] ?? false) {
            $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', '!=', 'customer'));
        }

        if ($status = $filters['status'] ?? null) {
            $query->withStatus($status);
        }

        [$sortColumn, $sortDirection] = $this->resolveSort(
            $filters['sort'] ?? null,
            $filters['direction'] ?? null,
        );

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function resolveSort(?string $sort, ?string $direction): array
    {
        $sortColumn = in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'created_at';
        $sortDirection = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        return [$sortColumn, $sortDirection];
    }
}
