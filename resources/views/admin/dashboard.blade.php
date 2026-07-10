@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $currency = config('shop.currency_symbol');
        $maxMonthly = max(1, (float) $monthlySales->max('total'));
    @endphp

    <div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-indigo-600">Executive Overview</p>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Operations Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">Real-time commerce, inventory, and customer metrics.</p>
        </div>
        <p class="text-xs text-gray-400">Updated {{ now()->format('M j, Y H:i') }}</p>
    </div>

    {{-- Order KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('admin.partials.stat-card', [
            'label' => 'Total Orders',
            'value' => number_format($kpis['total_orders']),
            'hint' => 'All time',
            'tone' => 'slate',
            'icon' => 'shopping-cart',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Pending Orders',
            'value' => number_format($kpis['pending_orders']),
            'hint' => 'Awaiting confirmation',
            'tone' => 'amber',
            'icon' => 'ticket',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Processing Orders',
            'value' => number_format($kpis['processing_orders']),
            'hint' => 'In fulfillment',
            'tone' => 'blue',
            'icon' => 'archive-box',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Delivered Orders',
            'value' => number_format($kpis['delivered_orders']),
            'hint' => 'Completed deliveries',
            'tone' => 'emerald',
            'icon' => 'cube',
        ])
    </div>

    {{-- Revenue --}}
    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
        @include('admin.partials.stat-card', [
            'label' => 'Total Revenue',
            'value' => $currency.' '.number_format($kpis['revenue'], 2),
            'hint' => 'Paid orders (excl. cancelled/refunded)',
            'tone' => 'violet',
            'icon' => 'credit-card',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'This Month',
            'value' => $currency.' '.number_format($kpis['month_revenue'], 2),
            'hint' => now()->format('F Y').' sales',
            'tone' => 'emerald',
            'icon' => 'chart-bar',
        ])
        @can('reports.view')
            @include('admin.partials.stat-card', [
                'label' => "Today's Sales",
                'value' => $currency.' '.number_format($reportWidgets['today_revenue'], 2),
                'hint' => $reportWidgets['today_orders'].' orders · '.now()->format('M j'),
                'tone' => 'blue',
                'icon' => 'ticket',
            ])
            @include('admin.partials.stat-card', [
                'label' => 'This Week',
                'value' => $currency.' '.number_format($reportWidgets['week_revenue'], 2),
                'hint' => $reportWidgets['week_orders'].' orders',
                'tone' => 'amber',
                'icon' => 'shopping-cart',
            ])
        @endcan
    </div>

    {{-- Monthly sales chart --}}
    <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60" x-data="{ hover: null }">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Monthly Sales</h3>
                <p class="text-sm text-gray-500">Paid revenue — last 6 months</p>
            </div>
            @can('reports.view')
                <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Full reports →</a>
            @endcan
        </div>
        <div class="flex h-48 items-end gap-3">
            @foreach ($monthlySales as $index => $month)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div class="relative flex h-36 w-full items-end justify-center">
                        <div
                            class="w-full max-w-[3rem] rounded-t-md bg-indigo-500 transition-all hover:bg-indigo-600"
                            style="height: {{ max(4, ($month['total'] / $maxMonthly) * 100) }}%"
                            @mouseenter="hover = {{ $index }}"
                            @mouseleave="hover = null"
                        ></div>
                        <div x-show="hover === {{ $index }}" x-transition class="absolute -top-10 rounded bg-slate-900 px-2 py-1 text-xs text-white whitespace-nowrap">
                            {{ $currency }} {{ number_format($month['total'], 0) }} · {{ $month['orders'] }} orders
                        </div>
                    </div>
                    <span class="text-[11px] font-medium text-gray-500">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Top products --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
            <h3 class="text-base font-semibold text-gray-900">Top Products</h3>
            <p class="text-sm text-gray-500 mb-4">By units sold</p>
            <div class="divide-y divide-gray-100">
                @forelse ($topProducts as $product)
                    <div class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $product->product_name }}</p>
                            <p class="text-gray-500">{{ number_format($product->units_sold) }} units</p>
                        </div>
                        <p class="font-semibold text-gray-900">{{ $currency }} {{ number_format($product->revenue, 2) }}</p>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">No sales data yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Low stock --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Low Stock Products</h3>
                    <p class="text-sm text-gray-500">Below warehouse threshold</p>
                </div>
                @can('inventory.view')
                    <a href="{{ route('admin.inventory.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View all</a>
                @endcan
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($lowStock as $item)
                    <div class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $item->productVariant?->product?->name ?? 'Product' }}</p>
                            <p class="text-gray-500">{{ $item->productVariant?->sku }} · {{ $item->warehouse?->name }}</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                            {{ $item->available_quantity }} left
                        </span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">Stock levels are healthy.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Order status breakdown --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 xl:col-span-1">
            <h3 class="text-base font-semibold text-gray-900">Orders by Status</h3>
            <p class="text-sm text-gray-500 mb-4">Live pipeline snapshot</p>
            <div class="space-y-3">
                @foreach ($orderStatusBreakdown as $row)
                    @if ($row['count'] > 0)
                        <a href="{{ route('admin.orders.index', ['status' => $row['status']->slug]) }}" class="flex items-center justify-between text-sm hover:bg-gray-50 -mx-2 px-2 py-1 rounded-lg">
                            <x-order-status-badge :status="$row['status']" />
                            <span class="font-semibold text-gray-900">{{ number_format($row['count']) }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Recent customers --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 xl:col-span-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Recent Customers</h3>
                @can('customers.view')
                    <a href="{{ route('admin.customers.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View all</a>
                @endcan
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($recentCustomers as $customer)
                    <a href="{{ route('admin.customers.show', $customer) }}" class="flex items-center justify-between py-3 text-sm hover:bg-gray-50 -mx-2 px-2 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ $customer->display_name }}</p>
                            <p class="text-gray-500">{{ $customer->user?->email }}</p>
                        </div>
                        <span class="text-xs text-gray-400">{{ $customer->updated_at?->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-gray-500">No customers yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 xl:col-span-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Recent Orders</h3>
                @can('orders.view')
                    <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View all</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="pb-2 font-medium">Order</th>
                            <th class="pb-2 font-medium">Customer</th>
                            <th class="pb-2 font-medium">Status</th>
                            <th class="pb-2 font-medium text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td class="py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $order->order_number }}</a>
                                </td>
                                <td class="py-3 text-gray-600">{{ $order->customer_name }}</td>
                                <td class="py-3"><x-order-status-badge :status="$order->status" /></td>
                                <td class="py-3 text-right font-medium">{{ $order->formatted_grand_total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-500">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
