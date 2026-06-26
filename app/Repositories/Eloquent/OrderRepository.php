<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Order);
    }

    public function search(?string $term = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()->withCount('items');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%");
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $filters['payment_status'] ?? null) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($from = $filters['date_from'] ?? null) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $filters['date_to'] ?? null) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::query()->where('order_number', $orderNumber)->first();
    }
}
