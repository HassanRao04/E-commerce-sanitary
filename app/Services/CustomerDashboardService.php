<?php

namespace App\Services;

use App\Models\User;

class CustomerDashboardService
{
    public function __construct(
        private readonly OrderWorkflowService $workflow,
    ) {}

    /** @return array<string, mixed> */
    public function stats(User $user): array
    {
        $ordersQuery = $user->orders();

        return [
            'total_orders' => (clone $ordersQuery)->count(),
            'pending_orders' => (clone $ordersQuery)->whereIn('status', $this->workflow->slugsForCustomerGroup('pending'))->count(),
            'processing_orders' => (clone $ordersQuery)->whereIn('status', $this->workflow->slugsForCustomerGroup('processing'))->count(),
            'delivered_orders' => (clone $ordersQuery)->whereIn('status', $this->workflow->slugsForCustomerGroup('delivered'))->count(),
            'total_spent' => (float) (clone $ordersQuery)
                ->whereNotIn('status', $this->workflow->revenueExcludedSlugs())
                ->sum('grand_total'),
            'recent_orders' => $this->recentOrders($user),
        ];
    }

    public function recentOrders(User $user, int $limit = 5): \Illuminate\Support\Collection
    {
        return $user->orders()
            ->with(['orderStatus'])
            ->withCount('items')
            ->with(['shipments' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
