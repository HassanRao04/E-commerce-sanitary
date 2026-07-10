<?php

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function data(): array
    {
        return [
            'kpis' => $this->kpis(),
            'monthlySales' => $this->monthlySales(),
            'topProducts' => $this->topProducts(),
            'lowStock' => $this->lowStock(),
            'recentCustomers' => $this->recentCustomers(),
            'recentOrders' => $this->recentOrders(),
            'orderStatusBreakdown' => $this->orderStatusBreakdown(),
            'reportWidgets' => $this->reportService->dashboardWidgets(),
        ];
    }

    /** @deprecated Use data()['kpis'] */
    public function stats(): array
    {
        $kpis = $this->kpis();

        return [
            'products' => Product::query()->count(),
            'active_products' => Product::query()->where('status', ProductStatus::Active)->count(),
            'orders' => $kpis['total_orders'],
            'pending_orders' => $kpis['pending_orders'],
            'customers' => User::query()->role('customer')->count(),
            'revenue' => $kpis['revenue'],
        ];
    }

    public function kpis(): array
    {
        $excluded = $this->workflow->revenueExcludedSlugs();
        $revenueQuery = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNotIn('status', $excluded);

        return [
            'total_orders' => Order::query()->count(),
            'pending_orders' => Order::query()->whereIn('status', $this->workflow->slugsForCustomerGroup('pending'))->count(),
            'processing_orders' => Order::query()->whereIn('status', $this->workflow->slugsForCustomerGroup('processing'))->count(),
            'delivered_orders' => Order::query()->whereIn('status', $this->workflow->slugsForCustomerGroup('delivered'))->count(),
            'revenue' => (float) (clone $revenueQuery)->sum('grand_total'),
            'month_revenue' => (float) (clone $revenueQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('grand_total'),
        ];
    }

    public function monthlySales(int $months = 6): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $excluded = $this->workflow->revenueExcludedSlugs();

        $grouped = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNotIn('status', $excluded)
            ->where('created_at', '>=', $start)
            ->get(['grand_total', 'created_at'])
            ->groupBy(fn (Order $order): string => $order->created_at->format('Y-m'))
            ->map(fn ($orders): array => [
                'total' => (float) $orders->sum('grand_total'),
                'orders' => $orders->count(),
            ]);

        return collect(range(0, $months - 1))->map(function (int $offset) use ($grouped, $start): array {
            $date = $start->copy()->addMonths($offset);
            $key = $date->format('Y-m');
            $row = $grouped->get($key, ['total' => 0, 'orders' => 0]);

            return [
                'label' => $date->format('M Y'),
                'total' => (float) $row['total'],
                'orders' => (int) $row['orders'],
            ];
        });
    }

    public function topProducts(int $limit = 5): Collection
    {
        return OrderItem::query()
            ->select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as units_sold')
            ->selectRaw('SUM(total) as revenue')
            ->whereNotNull('product_id')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();
    }

    public function lowStock(int $limit = 5): Collection
    {
        return Inventory::query()
            ->with(['productVariant.product', 'warehouse'])
            ->lowStock()
            ->orderByRaw('quantity_on_hand - quantity_reserved ASC')
            ->limit($limit)
            ->get();
    }

    public function recentCustomers(int $limit = 5): Collection
    {
        return Customer::query()
            ->with('user')
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function recentOrders(int $limit = 5): Collection
    {
        return Order::query()
            ->with('orderStatus')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function orderStatusBreakdown(): Collection
    {
        return $this->workflow->active()->map(function ($status): array {
            return [
                'status' => $status,
                'label' => $status->name,
                'count' => Order::query()->where('status', $status->slug)->count(),
            ];
        });
    }
}
