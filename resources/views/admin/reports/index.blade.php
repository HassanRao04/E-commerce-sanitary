@extends('layouts.admin')

@section('title', 'ERP Reporting')

@section('content')
    @php
        $currency = config('shop.currency_symbol');
        $categories = $widgets['categories'] ?? [];
    @endphp

    <div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-indigo-600">ERP Analytics</p>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Reporting Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">Sales, products, inventory, shipping, and customer insights from ERP transactions.</p>
        </div>
        <p class="text-xs text-gray-400">Updated {{ now()->format('M j, Y H:i') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        @include('admin.partials.stat-card', [
            'label' => "Today's Revenue",
            'value' => $currency.' '.number_format($widgets['today_revenue'], 2),
            'hint' => $widgets['today_orders'].' paid orders',
            'tone' => 'violet',
            'icon' => 'credit-card',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'This Week Revenue',
            'value' => $currency.' '.number_format($widgets['week_revenue'], 2),
            'hint' => $widgets['week_orders'].' paid orders',
            'tone' => 'emerald',
            'icon' => 'chart-bar',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Month Revenue',
            'value' => $currency.' '.number_format($categories['sales']['month_revenue'] ?? 0, 2),
            'hint' => number_format($categories['sales']['month_orders'] ?? 0).' orders · '.now()->format('F'),
            'tone' => 'blue',
            'icon' => 'shopping-cart',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Shipping Revenue',
            'value' => $currency.' '.number_format($categories['shipping']['shipping_revenue'] ?? 0, 2),
            'hint' => number_format($categories['shipping']['delivered_month'] ?? 0).' delivered this month',
            'tone' => 'amber',
            'icon' => 'cube',
        ])
    </div>

    @foreach ($groups as $groupKey => $group)
        @php $kpis = $categories[$groupKey] ?? []; @endphp
        <div class="mb-8">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $group['label'] }}</h3>
                    <p class="text-sm text-gray-500">
                        @switch($groupKey)
                            @case('sales')
                                Order revenue and payment breakdown from paid ERP transactions.
                                @break
                            @case('product')
                                Product and category performance from order line items.
                                @break
                            @case('inventory')
                                Live stock levels and valuation from inventory records.
                                @break
                            @case('shipping')
                                Shipment status, courier performance, and fulfillment detail.
                                @break
                            @case('customer')
                                Customer purchase activity and lifetime value.
                                @break
                            @case('influencer')
                                Influencer coupon sales, commission, usage, and repeat buyers.
                                @break
                        @endswitch
                    </p>
                </div>
                @if ($groupKey === 'sales')
                    <p class="text-xs text-gray-400">{{ number_format($kpis['month_orders'] ?? 0) }} orders · {{ $currency }} {{ number_format($kpis['month_revenue'] ?? 0, 2) }} this month</p>
                @elseif ($groupKey === 'product')
                    <p class="text-xs text-gray-400">{{ number_format($kpis['units_sold'] ?? 0) }} units · {{ number_format($kpis['products_sold'] ?? 0) }} products this month</p>
                @elseif ($groupKey === 'inventory')
                    <p class="text-xs text-gray-400">{{ number_format($kpis['low_stock'] ?? 0) }} low stock · {{ number_format($kpis['out_of_stock'] ?? 0) }} out of stock</p>
                @elseif ($groupKey === 'shipping')
                    <p class="text-xs text-gray-400">{{ number_format($kpis['pending'] ?? 0) }} pending · {{ number_format($kpis['in_transit'] ?? 0) }} in transit</p>
                @elseif ($groupKey === 'customer')
                    <p class="text-xs text-gray-400">{{ number_format($kpis['active_month'] ?? 0) }} active · {{ number_format($kpis['total_customers'] ?? 0) }} total customers</p>
                @elseif ($groupKey === 'influencer')
                    <p class="text-xs text-gray-400">{{ number_format($kpis['month_orders'] ?? 0) }} attributed orders · {{ $currency }} {{ number_format($kpis['month_commission'] ?? 0, 2) }} commission this month</p>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($group['reports'] as $report)
                    <a href="{{ route('admin.reports.show', $report['key']) }}"
                       class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 transition hover:ring-indigo-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-semibold text-gray-900 group-hover:text-indigo-700">{{ $report['label'] }}</h4>
                                <p class="mt-1 text-sm text-gray-500">{{ $report['description'] }}</p>
                            </div>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">Open</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
@endsection
