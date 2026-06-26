<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerDashboardService
{
    /** @return array<string, mixed> */
    public function stats(User $user): array
    {
        $ordersQuery = $user->orders();

        $pendingStatuses = [
            OrderStatus::Pending,
            OrderStatus::Confirmed,
        ];

        $processingStatuses = [
            OrderStatus::Processing,
            OrderStatus::Packed,
            OrderStatus::Shipped,
            OrderStatus::OutForDelivery,
        ];

        return [
            'total_orders' => (clone $ordersQuery)->count(),
            'pending_orders' => (clone $ordersQuery)->whereIn('status', $pendingStatuses)->count(),
            'processing_orders' => (clone $ordersQuery)->whereIn('status', $processingStatuses)->count(),
            'delivered_orders' => (clone $ordersQuery)->where('status', OrderStatus::Delivered)->count(),
            'total_spent' => (float) (clone $ordersQuery)
                ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded])
                ->sum('grand_total'),
            'recent_orders' => $this->recentOrders($user),
        ];
    }

    public function recentOrders(User $user, int $limit = 5): Collection
    {
        return $user->orders()
            ->withCount('items')
            ->with(['shipments' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
