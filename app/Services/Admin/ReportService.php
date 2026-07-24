<?php

namespace App\Services\Admin;

use App\Enums\CommissionLedgerStatus;
use App\Enums\CommissionLedgerType;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Customer;
use App\Models\InfluencerCommissionTransaction;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipping;
use App\Services\OrderWorkflowService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function type(string $type): array
    {
        $config = config("reports.types.{$type}");

        abort_if(empty($config), 404);

        return $config + ['key' => $type];
    }

    public function types(): Collection
    {
        return collect(config('reports.types', []))->map(
            fn (array $meta, string $key): array => $meta + ['key' => $key]
        );
    }

    public function groupedTypes(): Collection
    {
        $grouped = $this->types()->groupBy('group');

        return collect(array_keys(config('reports.groups', [])))
            ->filter(fn (string $group): bool => $grouped->has($group))
            ->mapWithKeys(fn (string $group): array => [
                $group => [
                    'label' => config("reports.groups.{$group}", str($group)->headline()->value()),
                    'reports' => $grouped->get($group),
                ],
            ]);
    }

    public function defaultRange(string $type): array
    {
        $meta = $this->type($type);
        $days = (int) ($meta['default_days'] ?? 30);

        return [
            'from' => now()->subDays(max(1, $days - 1))->startOfDay(),
            'to' => now()->endOfDay(),
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function build(string $type, Carbon $from, Carbon $to): array
    {
        return match ($type) {
            'daily-sales' => $this->dailySales($from, $to),
            'weekly-sales' => $this->weeklySales($from, $to),
            'monthly-sales' => $this->monthlySalesRange($from, $to),
            'yearly-sales' => $this->yearlySales($from, $to),
            'product-sales' => $this->productSales($from, $to),
            'category-sales' => $this->categorySales($from, $to),
            'customers' => $this->customerReport($from, $to),
            'inventory' => $this->inventoryReport(),
            'revenue' => $this->revenueReport($from, $to),
            'shipping-status' => $this->shippingStatusReport($from, $to),
            'shipping-courier' => $this->shippingCourierReport($from, $to),
            'shipping-fulfillment' => $this->shippingFulfillmentReport($from, $to),
            'influencer-monthly-sales' => $this->influencerMonthlySales($from, $to),
            'influencer-yearly-sales' => $this->influencerYearlySales($from, $to),
            'influencer-top' => $this->influencerTop($from, $to),
            'influencer-lowest' => $this->influencerLowest($from, $to),
            'influencer-commission' => $this->influencerHighestCommission($from, $to),
            'influencer-pending-payout' => $this->influencerPendingPayout($from, $to),
            'influencer-paid-payout' => $this->influencerPaidPayout($from, $to),
            'influencer-coupon-usage' => $this->influencerCouponUsage($from, $to),
            'influencer-monthly-commission' => $this->influencerMonthlyCommission($from, $to),
            'influencer-yearly-commission' => $this->influencerYearlyCommission($from, $to),
            'influencer-aov' => $this->influencerAverageOrderValue($from, $to),
            'influencer-repeat-customers' => $this->influencerRepeatCustomers($from, $to),
            default => abort(404),
        };
    }

    public function dashboardWidgets(): array
    {
        $paid = $this->paidOrdersQuery();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfDay();

        return [
            'today_revenue' => (float) (clone $paid)
                ->whereDate('created_at', today())
                ->sum('grand_total'),
            'today_orders' => (clone $paid)
                ->whereDate('created_at', today())
                ->count(),
            'week_revenue' => (float) (clone $paid)
                ->where('created_at', '>=', now()->startOfWeek())
                ->sum('grand_total'),
            'week_orders' => (clone $paid)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'categories' => [
                'sales' => [
                    'month_revenue' => (float) (clone $paid)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('grand_total'),
                    'month_orders' => (clone $paid)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->count(),
                ],
                'product' => [
                    'units_sold' => (int) OrderItem::query()
                        ->whereHas('order', fn (Builder $q) => $this->applyPaidOrderConstraints($q, $monthStart, $monthEnd))
                        ->sum('quantity'),
                    'products_sold' => (int) OrderItem::query()
                        ->whereHas('order', fn (Builder $q) => $this->applyPaidOrderConstraints($q, $monthStart, $monthEnd))
                        ->whereNotNull('product_id')
                        ->distinct('product_id')
                        ->count('product_id'),
                ],
                'inventory' => [
                    'low_stock' => Inventory::query()->lowStock()->count(),
                    'out_of_stock' => Inventory::query()->outOfStock()->count(),
                ],
                'shipping' => [
                    'pending' => Shipping::query()->pending()->count(),
                    'in_transit' => Shipping::query()->inTransit()->count(),
                    'delivered_month' => Shipping::query()
                        ->delivered()
                        ->where('delivered_at', '>=', $monthStart)
                        ->count(),
                    'shipping_revenue' => (float) (clone $paid)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('shipping_total'),
                ],
                'customer' => [
                    'total_customers' => Customer::query()->count(),
                    'active_month' => Customer::query()
                        ->whereHas('orders', fn (Builder $q) => $this->applyPaidOrderConstraints($q, $monthStart, $monthEnd))
                        ->count(),
                ],
                'influencer' => [
                    'month_revenue' => (float) (clone $paid)
                        ->whereNotNull('influencer_id')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('grand_total'),
                    'month_orders' => (clone $paid)
                        ->whereNotNull('influencer_id')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->count(),
                    'month_commission' => (float) (clone $paid)
                        ->whereNotNull('influencer_id')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('influencer_commission_amount'),
                ],
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function dailySales(Carbon $from, Carbon $to): array
    {
        $grouped = $this->aggregateOrdersByKey($from, $to, 'Y-m-d', 'M j, Y');

        return $this->salesPayload($grouped, 'Daily revenue');
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function weeklySales(Carbon $from, Carbon $to): array
    {
        $orders = $this->paidOrdersQuery()
            ->whereBetween('created_at', [$from, $to])
            ->get(['grand_total', 'created_at']);

        $grouped = $orders->groupBy(
            fn (Order $order): string => $order->created_at->copy()->startOfWeek()->format('Y-m-d')
        )->map(function (Collection $items, string $weekStart): array {
            return [
                'label' => 'Week of '.Carbon::parse($weekStart)->format('M j, Y'),
                'revenue' => (float) $items->sum('grand_total'),
                'orders' => $items->count(),
            ];
        })->sortKeys()->values();

        return $this->salesPayload($grouped, 'Weekly revenue');
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function monthlySalesRange(Carbon $from, Carbon $to): array
    {
        $grouped = $this->aggregateOrdersByKey($from, $to, 'Y-m', 'M Y');

        return $this->salesPayload($grouped, 'Monthly revenue');
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function yearlySales(Carbon $from, Carbon $to): array
    {
        $grouped = $this->aggregateOrdersByKey($from, $to, 'Y', 'Y');

        return $this->salesPayload($grouped, 'Yearly revenue');
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function productSales(Carbon $from, Carbon $to): array
    {
        $rows = OrderItem::query()
            ->select('product_id', 'product_name', 'sku')
            ->selectRaw('SUM(quantity) as units_sold')
            ->selectRaw('SUM(total) as revenue')
            ->whereHas('order', fn (Builder $q) => $this->applyPaidOrderConstraints($q, $from, $to))
            ->whereNotNull('product_id')
            ->groupBy('product_id', 'product_name', 'sku')
            ->orderByDesc('revenue')
            ->limit(100)
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->product_name,
                'sku' => $row->sku,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (float) $row->revenue,
            ]);

        $totalRevenue = (float) $rows->sum('revenue');
        $totalUnits = (int) $rows->sum('units_sold');

        return [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_units' => $totalUnits,
                'product_count' => $rows->count(),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Product revenue',
                'labels' => $rows->take(10)->pluck('label')->all(),
                'values' => $rows->take(10)->pluck('revenue')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function categorySales(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_categories', 'product_categories.product_id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'product_categories.category_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNotIn('orders.status', $this->workflow->revenueExcludedSlugs())
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('categories.id', 'categories.name')
            ->select('categories.name as label')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.total) as revenue')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->label,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (float) $row->revenue,
            ]);

        return [
            'summary' => [
                'total_revenue' => (float) $rows->sum('revenue'),
                'total_units' => (int) $rows->sum('units_sold'),
                'category_count' => $rows->count(),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'doughnut',
                'label' => 'Category revenue share',
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('revenue')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function customerReport(Carbon $from, Carbon $to): array
    {
        $rows = Customer::query()
            ->with('user:id,name,email,phone')
            ->whereHas('orders', fn (Builder $q) => $this->applyPaidOrderConstraints($q, $from, $to))
            ->withCount(['orders as orders_count' => fn (Builder $q) => $this->applyPaidOrderConstraints($q, $from, $to)])
            ->withSum(['orders as period_revenue' => fn (Builder $q) => $this->applyPaidOrderConstraints($q, $from, $to)], 'grand_total')
            ->orderByDesc('period_revenue')
            ->limit(100)
            ->get()
            ->map(fn (Customer $customer): array => [
                'label' => $customer->user?->name ?? 'Customer #'.$customer->id,
                'email' => $customer->user?->email,
                'customer_type' => $customer->customer_type?->value ?? 'retail',
                'orders_count' => (int) $customer->orders_count,
                'period_revenue' => (float) ($customer->period_revenue ?? 0),
                'lifetime_spend' => (float) $customer->lifetime_spend,
            ]);

        return [
            'summary' => [
                'active_customers' => $rows->count(),
                'total_revenue' => (float) $rows->sum('period_revenue'),
                'total_orders' => (int) $rows->sum('orders_count'),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Top customers by revenue',
                'labels' => $rows->take(10)->pluck('label')->all(),
                'values' => $rows->take(10)->pluck('period_revenue')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function inventoryReport(): array
    {
        $items = Inventory::query()
            ->with(['productVariant.product', 'warehouse'])
            ->orderByRaw('quantity_on_hand - quantity_reserved ASC')
            ->limit(200)
            ->get()
            ->map(function (Inventory $item): array {
                $variant = $item->productVariant;
                $price = (float) ($variant?->sale_price ?? $variant?->price ?? 0);

                return [
                    'label' => $item->productVariant?->product?->name ?? 'Variant #'.$item->product_variant_id,
                    'sku' => $item->productVariant?->sku,
                    'warehouse' => $item->warehouse?->name,
                    'on_hand' => (int) $item->quantity_on_hand,
                    'reserved' => (int) $item->quantity_reserved,
                    'available' => (int) $item->available_quantity,
                    'status' => $item->is_out_of_stock ? 'Out of stock' : ($item->is_low_stock ? 'Low stock' : 'In stock'),
                    'valuation' => round($item->available_quantity * $price, 2),
                ];
            });

        return [
            'summary' => [
                'sku_count' => $items->count(),
                'low_stock' => $items->where('status', 'Low stock')->count(),
                'out_of_stock' => $items->where('status', 'Out of stock')->count(),
                'total_valuation' => (float) $items->sum('valuation'),
            ],
            'rows' => $items,
            'chart' => [
                'type' => 'bar',
                'label' => 'Inventory valuation (top items)',
                'labels' => $items->sortByDesc('valuation')->take(10)->pluck('label')->values()->all(),
                'values' => $items->sortByDesc('valuation')->take(10)->pluck('valuation')->values()->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function shippingStatusReport(Carbon $from, Carbon $to): array
    {
        $shipments = Shipping::query()
            ->whereBetween('created_at', [$from, $to])
            ->with('order:id,shipping_total')
            ->get();

        $rows = $shipments
            ->groupBy(fn (Shipping $shipment): string => $shipment->status?->value ?? 'unknown')
            ->map(function (Collection $group, string $status): array {
                return [
                    'label' => str($status)->headline()->replace('_', ' ')->value(),
                    'shipments' => $group->count(),
                    'shipping_revenue' => (float) $group->sum(fn (Shipping $shipment): float => (float) ($shipment->order?->shipping_total ?? 0)),
                ];
            })
            ->sortByDesc('shipments')
            ->values();

        return [
            'summary' => [
                'total_shipments' => $shipments->count(),
                'shipping_revenue' => (float) $rows->sum('shipping_revenue'),
                'delivered' => $shipments->where('status', ShipmentStatus::Delivered)->count(),
                'in_transit' => $shipments->filter(fn (Shipping $shipment): bool => $shipment->is_in_transit)->count(),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'doughnut',
                'label' => 'Shipments by status',
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('shipments')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function shippingCourierReport(Carbon $from, Carbon $to): array
    {
        $shipments = Shipping::query()
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $rows = $shipments
            ->groupBy(fn (Shipping $shipment): string => filled($shipment->courier_name) ? $shipment->courier_name : 'Unassigned')
            ->map(function (Collection $group, string $courier): array {
                $delivered = $group->filter(fn (Shipping $shipment): bool => $shipment->status === ShipmentStatus::Delivered);
                $deliveryDays = $delivered
                    ->filter(fn (Shipping $shipment): bool => filled($shipment->shipped_at) && filled($shipment->delivered_at))
                    ->map(fn (Shipping $shipment): int => (int) $shipment->shipped_at->diffInDays($shipment->delivered_at));

                return [
                    'label' => $courier,
                    'shipments' => $group->count(),
                    'delivered' => $delivered->count(),
                    'avg_delivery_days' => $deliveryDays->isNotEmpty()
                        ? round((float) $deliveryDays->avg(), 1)
                        : null,
                ];
            })
            ->sortByDesc('shipments')
            ->values();

        $withDeliveryTimes = $rows->filter(fn (array $row): bool => $row['avg_delivery_days'] !== null);

        return [
            'summary' => [
                'courier_count' => $rows->count(),
                'total_shipments' => (int) $rows->sum('shipments'),
                'delivered' => (int) $rows->sum('delivered'),
                'avg_delivery_days' => $withDeliveryTimes->isNotEmpty()
                    ? round((float) $withDeliveryTimes->avg('avg_delivery_days'), 1)
                    : 0.0,
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Shipments by courier',
                'labels' => $rows->take(10)->pluck('label')->all(),
                'values' => $rows->take(10)->pluck('shipments')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function shippingFulfillmentReport(Carbon $from, Carbon $to): array
    {
        $rows = Shipping::query()
            ->whereBetween('created_at', [$from, $to])
            ->with('order:id,order_number,shipping_total')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn (Shipping $shipment): array => [
                'label' => $shipment->order?->order_number ?? 'Order #'.$shipment->order_id,
                'courier' => $shipment->courier_name ?? '—',
                'tracking' => $shipment->tracking_number ?? '—',
                'status' => str($shipment->status?->value ?? 'unknown')->headline()->replace('_', ' ')->value(),
                'shipped_at' => $shipment->shipped_at?->format('M j, Y') ?? '—',
                'delivered_at' => $shipment->delivered_at?->format('M j, Y') ?? '—',
                'shipping_fee' => (float) ($shipment->order?->shipping_total ?? 0),
            ]);

        return [
            'summary' => [
                'total_shipments' => $rows->count(),
                'delivered' => $rows->filter(fn (array $row): bool => str($row['status'])->lower()->value() === 'delivered')->count(),
                'shipping_revenue' => (float) $rows->sum('shipping_fee'),
                'with_tracking' => $rows->filter(fn (array $row): bool => $row['tracking'] !== '—')->count(),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Recent shipments',
                'labels' => $rows->take(10)->pluck('label')->all(),
                'values' => $rows->take(10)->pluck('shipping_fee')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function revenueReport(Carbon $from, Carbon $to): array
    {
        $orders = $this->paidOrdersQuery()
            ->whereBetween('created_at', [$from, $to])
            ->get([
                'payment_method',
                'subtotal',
                'discount_total',
                'shipping_total',
                'tax_total',
                'grand_total',
            ]);

        $byMethod = $orders->groupBy(fn (Order $order) => $order->payment_method?->value ?? 'unknown')
            ->map(fn (Collection $group, string $method): array => [
                'label' => str($method)->headline()->replace('_', ' ')->value(),
                'orders' => $group->count(),
                'revenue' => (float) $group->sum('grand_total'),
            ])->values();

        return [
            'summary' => [
                'gross_revenue' => (float) $orders->sum('grand_total'),
                'subtotal' => (float) $orders->sum('subtotal'),
                'discounts' => (float) $orders->sum('discount_total'),
                'shipping' => (float) $orders->sum('shipping_total'),
                'tax' => (float) $orders->sum('tax_total'),
                'orders' => $orders->count(),
            ],
            'rows' => $byMethod,
            'chart' => [
                'type' => 'doughnut',
                'label' => 'Revenue by payment method',
                'labels' => $byMethod->pluck('label')->all(),
                'values' => $byMethod->pluck('revenue')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerMonthlySales(Carbon $from, Carbon $to): array
    {
        return $this->salesPayload(
            $this->aggregateInfluencerOrdersByKey($from, $to, 'Y-m', 'M Y'),
            'Influencer monthly revenue',
        );
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerYearlySales(Carbon $from, Carbon $to): array
    {
        return $this->salesPayload(
            $this->aggregateInfluencerOrdersByKey($from, $to, 'Y', 'Y'),
            'Influencer yearly revenue',
        );
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerTop(Carbon $from, Carbon $to): array
    {
        $rows = $this->influencerRankingRows($from, $to)
            ->sortByDesc('revenue')
            ->take(20)
            ->values();

        return $this->influencerRankingPayload($rows, 'Top influencer revenue', 'bar');
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerLowest(Carbon $from, Carbon $to): array
    {
        $rows = $this->influencerRankingRows($from, $to)
            ->sortBy('revenue')
            ->take(20)
            ->values();

        return $this->influencerRankingPayload($rows, 'Lowest influencer revenue', 'bar');
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerHighestCommission(Carbon $from, Carbon $to): array
    {
        $rows = $this->influencerRankingRows($from, $to)
            ->sortByDesc('commission')
            ->take(20)
            ->values();

        return [
            'summary' => [
                'total_commission' => (float) $rows->sum('commission'),
                'total_revenue' => (float) $rows->sum('revenue'),
                'influencer_count' => $rows->count(),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Commission generated',
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('commission')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerPendingPayout(Carbon $from, Carbon $to): array
    {
        $rows = $this->influencerAttributedOrdersQuery($from, $to)
            ->whereNull('influencer_commission_paid_at')
            ->with(['influencer:id,name', 'trackedCoupon:id,code'])
            ->latest('created_at')
            ->get([
                'id',
                'order_number',
                'influencer_id',
                'coupon_id',
                'coupon_code',
                'grand_total',
                'influencer_commission_amount',
                'created_at',
            ])
            ->map(fn (Order $order): array => [
                'label' => $order->order_number,
                'influencer' => $order->influencer?->name ?? '—',
                'coupon' => $order->trackedCoupon?->code ?? $order->coupon_code ?? '—',
                'date' => $order->created_at?->format('Y-m-d') ?? '—',
                'revenue' => round((float) $order->grand_total, 2),
                'commission' => round((float) $order->influencer_commission_amount, 2),
                'status' => 'Pending',
            ])
            ->values();

        return [
            'summary' => [
                'pending_orders' => $rows->count(),
                'pending_commission' => (float) $rows->sum('commission'),
                'pending_revenue' => (float) $rows->sum('revenue'),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Pending commission by order',
                'labels' => $rows->take(20)->pluck('label')->all(),
                'values' => $rows->take(20)->pluck('commission')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerPaidPayout(Carbon $from, Carbon $to): array
    {
        $rows = InfluencerCommissionTransaction::query()
            ->where('type', CommissionLedgerType::Debit)
            ->where('status', CommissionLedgerStatus::Completed)
            ->whereBetween('created_at', [$from, $to])
            ->with([
                'influencer:id,name',
                'referenceOrder:id,order_number',
                'creator:id,name,first_name,last_name',
            ])
            ->latest('created_at')
            ->get()
            ->map(fn (InfluencerCommissionTransaction $tx): array => [
                'label' => $tx->referenceOrder?->order_number ?? 'Manual payout',
                'influencer' => $tx->influencer?->name ?? '—',
                'admin' => $tx->creator?->name ?? '—',
                'date' => $tx->created_at?->format('Y-m-d') ?? '—',
                'amount' => round((float) ($tx->amount ?? 0), 2),
                'payment_note' => $tx->admin_notes ?? '—',
                'transaction_id' => $tx->transaction_id ?? '—',
                'status' => $tx->displayStatus(),
            ])
            ->values();

        return [
            'summary' => [
                'payout_count' => $rows->count(),
                'total_paid' => (float) $rows->sum('amount'),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Paid payouts',
                'labels' => $rows->take(20)->pluck('label')->all(),
                'values' => $rows->take(20)->pluck('amount')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerMonthlyCommission(Carbon $from, Carbon $to): array
    {
        return $this->commissionPayload(
            $this->aggregateInfluencerCommissionByKey($from, $to, 'Y-m', 'M Y'),
            'Monthly commission',
        );
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerYearlyCommission(Carbon $from, Carbon $to): array
    {
        return $this->commissionPayload(
            $this->aggregateInfluencerCommissionByKey($from, $to, 'Y', 'Y'),
            'Yearly commission',
        );
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerCouponUsage(Carbon $from, Carbon $to): array
    {
        $rows = $this->influencerAttributedOrdersQuery($from, $to)
            ->with(['trackedCoupon:id,code', 'influencer:id,name'])
            ->get([
                'id',
                'coupon_id',
                'coupon_code',
                'influencer_id',
                'grand_total',
                'discount_total',
                'influencer_commission_amount',
            ])
            ->groupBy(fn (Order $order): string => (string) ($order->coupon_id ?: $order->coupon_code ?: 'unknown'))
            ->map(function (Collection $orders): array {
                $first = $orders->first();

                return [
                    'label' => $first->trackedCoupon?->code ?? $first->coupon_code ?? '—',
                    'influencer' => $first->influencer?->name ?? '—',
                    'uses' => $orders->count(),
                    'revenue' => round((float) $orders->sum('grand_total'), 2),
                    'discount' => round((float) $orders->sum('discount_total'), 2),
                    'commission' => round((float) $orders->sum('influencer_commission_amount'), 2),
                ];
            })
            ->sortByDesc('uses')
            ->values();

        return [
            'summary' => [
                'total_uses' => (int) $rows->sum('uses'),
                'total_revenue' => (float) $rows->sum('revenue'),
                'total_discount' => (float) $rows->sum('discount'),
                'coupon_count' => $rows->count(),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Coupon uses',
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('uses')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerAverageOrderValue(Carbon $from, Carbon $to): array
    {
        $rows = $this->influencerRankingRows($from, $to)
            ->sortByDesc('aov')
            ->take(20)
            ->values();

        $totalOrders = (int) $rows->sum('orders');
        $totalRevenue = (float) $rows->sum('revenue');

        return [
            'summary' => [
                'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0,
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Average order value',
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('aov')->all(),
            ],
        ];
    }

    /** @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>} */
    public function influencerRepeatCustomers(Carbon $from, Carbon $to): array
    {
        $rows = $this->influencerAttributedOrdersQuery($from, $to)
            ->get([
                'user_id',
                'customer_name',
                'customer_email',
                'grand_total',
                'influencer_commission_amount',
            ])
            ->groupBy(fn (Order $order): string => (string) ($order->user_id ?: $order->customer_email))
            ->map(function (Collection $orders): array {
                $first = $orders->first();

                return [
                    'label' => $first->customer_name,
                    'email' => $first->customer_email,
                    'orders' => $orders->count(),
                    'revenue' => round((float) $orders->sum('grand_total'), 2),
                    'commission' => round((float) $orders->sum('influencer_commission_amount'), 2),
                ];
            })
            ->filter(fn (array $row): bool => $row['orders'] >= 2)
            ->sortByDesc('orders')
            ->values();

        return [
            'summary' => [
                'repeat_customers' => $rows->count(),
                'total_orders' => (int) $rows->sum('orders'),
                'total_revenue' => (float) $rows->sum('revenue'),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'bar',
                'label' => 'Repeat customer orders',
                'labels' => $rows->take(20)->pluck('label')->all(),
                'values' => $rows->take(20)->pluck('orders')->all(),
            ],
        ];
    }

    public function exportHeaders(string $type): array
    {
        return match ($type) {
            'daily-sales', 'weekly-sales', 'monthly-sales', 'yearly-sales' => ['Period', 'Orders', 'Revenue'],
            'product-sales' => ['Product', 'SKU', 'Units Sold', 'Revenue'],
            'category-sales' => ['Category', 'Units Sold', 'Revenue'],
            'customers' => ['Customer', 'Email', 'Type', 'Orders', 'Period Revenue', 'Lifetime Spend'],
            'inventory' => ['Product', 'SKU', 'Warehouse', 'On Hand', 'Reserved', 'Available', 'Status', 'Valuation'],
            'revenue' => ['Payment Method', 'Orders', 'Revenue'],
            'shipping-status' => ['Status', 'Shipments', 'Shipping Revenue'],
            'shipping-courier' => ['Courier', 'Shipments', 'Delivered', 'Avg Delivery Days'],
            'shipping-fulfillment' => ['Order', 'Courier', 'Tracking', 'Status', 'Shipped', 'Delivered', 'Shipping Fee'],
            'influencer-monthly-sales', 'influencer-yearly-sales' => ['Period', 'Orders', 'Revenue'],
            'influencer-monthly-commission', 'influencer-yearly-commission' => ['Period', 'Orders', 'Commission'],
            'influencer-top', 'influencer-lowest' => ['Influencer', 'Orders', 'Revenue', 'Commission', 'AOV'],
            'influencer-commission' => ['Influencer', 'Orders', 'Commission', 'Revenue'],
            'influencer-pending-payout' => ['Order', 'Influencer', 'Coupon', 'Date', 'Revenue', 'Commission', 'Status'],
            'influencer-paid-payout' => ['Reference', 'Influencer', 'Admin', 'Date', 'Amount', 'Payment Note', 'Transaction ID', 'Status'],
            'influencer-coupon-usage' => ['Coupon', 'Influencer', 'Uses', 'Revenue', 'Discount', 'Commission'],
            'influencer-aov' => ['Influencer', 'Orders', 'Revenue', 'AOV'],
            'influencer-repeat-customers' => ['Customer', 'Email', 'Orders', 'Revenue', 'Commission'],
            default => ['Label', 'Value'],
        };
    }

    /** @return array<int, array<int, string|float|int|null>> */
    public function exportRows(string $type, Collection $rows): array
    {
        return match ($type) {
            'daily-sales', 'weekly-sales', 'monthly-sales', 'yearly-sales',
            'influencer-monthly-sales', 'influencer-yearly-sales' => $rows->map(fn ($r) => [
                $r['label'], $r['orders'], $r['revenue'],
            ])->all(),
            'influencer-monthly-commission', 'influencer-yearly-commission' => $rows->map(fn ($r) => [
                $r['label'], $r['orders'], $r['commission'],
            ])->all(),
            'product-sales' => $rows->map(fn ($r) => [
                $r['label'], $r['sku'], $r['units_sold'], $r['revenue'],
            ])->all(),
            'category-sales' => $rows->map(fn ($r) => [
                $r['label'], $r['units_sold'], $r['revenue'],
            ])->all(),
            'customers' => $rows->map(fn ($r) => [
                $r['label'], $r['email'], $r['customer_type'], $r['orders_count'], $r['period_revenue'], $r['lifetime_spend'],
            ])->all(),
            'inventory' => $rows->map(fn ($r) => [
                $r['label'], $r['sku'], $r['warehouse'], $r['on_hand'], $r['reserved'], $r['available'], $r['status'], $r['valuation'],
            ])->all(),
            'revenue' => $rows->map(fn ($r) => [
                $r['label'], $r['orders'], $r['revenue'],
            ])->all(),
            'shipping-status' => $rows->map(fn ($r) => [
                $r['label'], $r['shipments'], $r['shipping_revenue'],
            ])->all(),
            'shipping-courier' => $rows->map(fn ($r) => [
                $r['label'], $r['shipments'], $r['delivered'], $r['avg_delivery_days'] ?? '—',
            ])->all(),
            'shipping-fulfillment' => $rows->map(fn ($r) => [
                $r['label'], $r['courier'], $r['tracking'], $r['status'], $r['shipped_at'], $r['delivered_at'], $r['shipping_fee'],
            ])->all(),
            'influencer-top', 'influencer-lowest' => $rows->map(fn ($r) => [
                $r['label'], $r['orders'], $r['revenue'], $r['commission'], $r['aov'],
            ])->all(),
            'influencer-commission' => $rows->map(fn ($r) => [
                $r['label'], $r['orders'], $r['commission'], $r['revenue'],
            ])->all(),
            'influencer-pending-payout' => $rows->map(fn ($r) => [
                $r['label'], $r['influencer'], $r['coupon'], $r['date'], $r['revenue'], $r['commission'], $r['status'],
            ])->all(),
            'influencer-paid-payout' => $rows->map(fn ($r) => [
                $r['label'], $r['influencer'], $r['admin'], $r['date'], $r['amount'], $r['payment_note'], $r['transaction_id'], $r['status'],
            ])->all(),
            'influencer-coupon-usage' => $rows->map(fn ($r) => [
                $r['label'], $r['influencer'], $r['uses'], $r['revenue'], $r['discount'], $r['commission'],
            ])->all(),
            'influencer-aov' => $rows->map(fn ($r) => [
                $r['label'], $r['orders'], $r['revenue'], $r['aov'],
            ])->all(),
            'influencer-repeat-customers' => $rows->map(fn ($r) => [
                $r['label'], $r['email'], $r['orders'], $r['revenue'], $r['commission'],
            ])->all(),
            default => [],
        };
    }

    private function paidOrdersQuery(): Builder
    {
        return Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNotIn('status', $this->workflow->revenueExcludedSlugs());
    }

    private function applyPaidOrderConstraints(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->where('orders.payment_status', PaymentStatus::Paid)
            ->whereNotIn('orders.status', $this->workflow->revenueExcludedSlugs())
            ->whereBetween('orders.created_at', [$from, $to]);
    }

    private function aggregateOrdersByKey(Carbon $from, Carbon $to, string $keyFormat, string $labelFormat): Collection
    {
        return $this->paidOrdersQuery()
            ->whereBetween('created_at', [$from, $to])
            ->get(['grand_total', 'created_at'])
            ->groupBy(fn (Order $order): string => $order->created_at->format($keyFormat))
            ->sortKeys()
            ->map(fn (Collection $items, string $key): array => [
                'label' => Carbon::parse($key)->format($labelFormat),
                'orders' => $items->count(),
                'revenue' => (float) $items->sum('grand_total'),
            ])
            ->values();
    }

    /** @param Collection<int, array{label: string, orders: int, revenue: float}> $rows */
    private function salesPayload(Collection $rows, string $chartLabel): array
    {
        return [
            'summary' => [
                'total_revenue' => (float) $rows->sum('revenue'),
                'total_orders' => (int) $rows->sum('orders'),
                'average_order_value' => $rows->sum('orders') > 0
                    ? round($rows->sum('revenue') / $rows->sum('orders'), 2)
                    : 0.0,
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'line',
                'label' => $chartLabel,
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('revenue')->all(),
            ],
        ];
    }

    private function influencerAttributedOrdersQuery(Carbon $from, Carbon $to): Builder
    {
        return $this->paidOrdersQuery()
            ->whereNotNull('influencer_id')
            ->whereBetween('created_at', [$from, $to]);
    }

    private function aggregateInfluencerOrdersByKey(
        Carbon $from,
        Carbon $to,
        string $keyFormat,
        string $labelFormat,
    ): Collection {
        return $this->influencerAttributedOrdersQuery($from, $to)
            ->get(['grand_total', 'created_at'])
            ->groupBy(fn (Order $order): string => $order->created_at->format($keyFormat))
            ->sortKeys()
            ->map(fn (Collection $items, string $key): array => [
                'label' => Carbon::parse($key)->format($labelFormat),
                'orders' => $items->count(),
                'revenue' => (float) $items->sum('grand_total'),
            ])
            ->values();
    }

    private function aggregateInfluencerCommissionByKey(
        Carbon $from,
        Carbon $to,
        string $keyFormat,
        string $labelFormat,
    ): Collection {
        return $this->influencerAttributedOrdersQuery($from, $to)
            ->get(['influencer_commission_amount', 'created_at'])
            ->groupBy(fn (Order $order): string => $order->created_at->format($keyFormat))
            ->sortKeys()
            ->map(fn (Collection $items, string $key): array => [
                'label' => Carbon::parse($key)->format($labelFormat),
                'orders' => $items->count(),
                'commission' => round((float) $items->sum('influencer_commission_amount'), 2),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array{label: string, orders: int, commission: float}>  $rows
     * @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>}
     */
    private function commissionPayload(Collection $rows, string $chartLabel): array
    {
        return [
            'summary' => [
                'total_commission' => (float) $rows->sum('commission'),
                'total_orders' => (int) $rows->sum('orders'),
                'average_commission' => $rows->sum('orders') > 0
                    ? round($rows->sum('commission') / $rows->sum('orders'), 2)
                    : 0.0,
            ],
            'rows' => $rows,
            'chart' => [
                'type' => 'line',
                'label' => $chartLabel,
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('commission')->all(),
            ],
        ];
    }

    /** @return Collection<int, array{label: string, orders: int, revenue: float, commission: float, aov: float}> */
    private function influencerRankingRows(Carbon $from, Carbon $to): Collection
    {
        return $this->influencerAttributedOrdersQuery($from, $to)
            ->with('influencer:id,name')
            ->get([
                'influencer_id',
                'grand_total',
                'influencer_commission_amount',
            ])
            ->groupBy('influencer_id')
            ->map(function (Collection $orders): array {
                $first = $orders->first();
                $ordersCount = $orders->count();
                $revenue = round((float) $orders->sum('grand_total'), 2);

                return [
                    'label' => $first->influencer?->name ?? 'Unknown',
                    'orders' => $ordersCount,
                    'revenue' => $revenue,
                    'commission' => round((float) $orders->sum('influencer_commission_amount'), 2),
                    'aov' => $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0.0,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{label: string, orders: int, revenue: float, commission: float, aov: float}>  $rows
     * @return array{summary: array<string, float|int>, rows: Collection, chart: array<string, mixed>}
     */
    private function influencerRankingPayload(Collection $rows, string $chartLabel, string $chartType = 'bar'): array
    {
        return [
            'summary' => [
                'total_revenue' => (float) $rows->sum('revenue'),
                'total_orders' => (int) $rows->sum('orders'),
                'total_commission' => (float) $rows->sum('commission'),
                'influencer_count' => $rows->count(),
            ],
            'rows' => $rows,
            'chart' => [
                'type' => $chartType,
                'label' => $chartLabel,
                'labels' => $rows->pluck('label')->all(),
                'values' => $rows->pluck('revenue')->all(),
            ],
        ];
    }
}
