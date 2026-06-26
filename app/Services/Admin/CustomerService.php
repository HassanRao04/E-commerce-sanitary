<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->customers->search($filters['q'] ?? null, $filters, $perPage);
    }

    public function findWithRelations(int $id): Customer
    {
        return Customer::query()
            ->with(['user.addresses'])
            ->withCount('orders')
            ->findOrFail($id);
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $old = $customer->toArray();
            $customer = $this->customers->update($customer, $data);
            $this->activityLog->log('customer.updated', $customer, $old, $customer->toArray());

            return $customer->fresh('user');
        });
    }
}
