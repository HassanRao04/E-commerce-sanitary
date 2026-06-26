<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Customer);
    }

    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::query()->with('user')->withCount('orders');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('company_name', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($uq) => $uq
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%"));
            });
        }

        if ($type = $filters['customer_type'] ?? null) {
            $query->where('customer_type', $type);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
