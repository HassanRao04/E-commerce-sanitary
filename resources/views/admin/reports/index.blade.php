@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
    @php $currency = config('shop.currency_symbol'); @endphp

    @include('admin.partials.page-header', ['title' => 'Business Reports'])
    <p class="-mt-4 mb-6 text-sm text-gray-500">Sales, inventory, customers, and revenue analytics with export options.</p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        @include('admin.partials.stat-card', [
            'label' => "Today's Revenue",
            'value' => $currency.' '.number_format($widgets['today_revenue'], 2),
            'hint' => $widgets['today_orders'].' orders',
            'tone' => 'violet',
            'icon' => 'credit-card',
        ])
        @include('admin.partials.stat-card', [
            'label' => "Today's Orders",
            'value' => number_format($widgets['today_orders']),
            'hint' => now()->format('M j, Y'),
            'tone' => 'slate',
            'icon' => 'shopping-cart',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'This Week Revenue',
            'value' => $currency.' '.number_format($widgets['week_revenue'], 2),
            'hint' => $widgets['week_orders'].' orders',
            'tone' => 'emerald',
            'icon' => 'chart-bar',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'This Week Orders',
            'value' => number_format($widgets['week_orders']),
            'hint' => 'Week to date',
            'tone' => 'blue',
            'icon' => 'ticket',
        ])
    </div>

    @foreach ($types as $group => $reports)
        <div class="mb-8">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">{{ str($group)->headline() }}</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($reports as $report)
                    <a href="{{ route('admin.reports.show', $report['key']) }}"
                       class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 transition hover:ring-indigo-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-semibold text-gray-900 group-hover:text-indigo-700">{{ $report['label'] }}</h4>
                                <p class="mt-1 text-sm text-gray-500">{{ $report['description'] }}</p>
                            </div>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">View</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
@endsection
