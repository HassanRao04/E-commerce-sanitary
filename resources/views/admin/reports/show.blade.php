@extends('layouts.admin')

@section('title', $meta['label'])

@section('content')
    @php
        $currency = config('shop.currency_symbol');
        $summary = $report['summary'];
        $rows = $report['rows'];
        $chart = $report['chart'];
        $isInventory = $meta['key'] === 'inventory';
        $moneyFields = ['revenue', 'period_revenue', 'lifetime_spend', 'valuation', 'gross_revenue', 'subtotal', 'discounts', 'shipping', 'tax', 'total_revenue', 'average_order_value', 'total_valuation', 'shipping_revenue', 'shipping_fee'];
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← Reporting dashboard</a>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">{{ $meta['label'] }}</h2>
            <p class="text-sm text-gray-500">{{ $meta['description'] }}</p>
        </div>

        <div class="flex flex-wrap items-end gap-2">
            @unless ($isInventory)
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="from" class="block text-xs font-medium text-gray-500">From</label>
                        <input type="date" name="from" id="from" value="{{ $from }}" class="rounded-md border-gray-300 text-sm shadow-sm">
                    </div>
                    <div>
                        <label for="to" class="block text-xs font-medium text-gray-500">To</label>
                        <input type="date" name="to" id="to" value="{{ $to }}" class="rounded-md border-gray-300 text-sm shadow-sm">
                    </div>
                    <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Apply</button>
                </form>
            @endunless

            @php $exportQuery = $isInventory ? [] : ['from' => $from, 'to' => $to]; @endphp
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.export', array_merge([$meta['key'], 'csv'], $exportQuery)) }}"
                   class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">CSV</a>
                <a href="{{ route('admin.reports.export', array_merge([$meta['key'], 'excel'], $exportQuery)) }}"
                   class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Excel</a>
                <a href="{{ route('admin.reports.export', array_merge([$meta['key'], 'pdf'], $exportQuery)) }}"
                   class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">PDF</a>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($summary as $label => $value)
            @php
                $formatted = (is_float($value) || is_string($value)) && (
                    str_contains((string) $label, 'revenue')
                    || str_contains((string) $label, 'valuation')
                    || str_contains((string) $label, 'spend')
                    || in_array($label, $moneyFields, true)
                )
                    ? $currency.' '.number_format((float) $value, 2)
                    : number_format((float) $value);
            @endphp
            @include('admin.partials.stat-card', [
                'label' => str($label)->headline()->replace('_', ' ')->value(),
                'value' => $formatted,
                'tone' => 'slate',
            ])
        @endforeach
    </div>

    <div class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
        <h3 class="mb-4 text-base font-semibold text-gray-900">{{ $chart['label'] ?? 'Chart' }}</h3>
        <div class="h-80">
            <canvas id="reportChart"></canvas>
        </div>
        @if ($rows->isEmpty())
            <p class="mt-4 text-center text-sm text-gray-500">No chart data for this period.</p>
        @endif
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">Detailed Data</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach ($tableHeaders as $header)
                            <th class="px-4 py-3 text-left font-medium text-gray-500">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            @switch($meta['key'])
                                @case('daily-sales')
                                @case('weekly-sales')
                                @case('monthly-sales')
                                @case('yearly-sales')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['orders']) }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['revenue'], 2) }}</td>
                                    @break
                                @case('product-sales')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ $row['sku'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['units_sold']) }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['revenue'], 2) }}</td>
                                    @break
                                @case('category-sales')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['units_sold']) }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['revenue'], 2) }}</td>
                                    @break
                                @case('customers')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ $row['email'] }}</td>
                                    <td class="px-4 py-3">{{ str($row['customer_type'])->headline() }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['orders_count']) }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['period_revenue'], 2) }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['lifetime_spend'], 2) }}</td>
                                    @break
                                @case('inventory')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ $row['sku'] }}</td>
                                    <td class="px-4 py-3">{{ $row['warehouse'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['on_hand']) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['reserved']) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['available']) }}</td>
                                    <td class="px-4 py-3">{{ $row['status'] }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['valuation'], 2) }}</td>
                                    @break
                                @case('revenue')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['orders']) }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['revenue'], 2) }}</td>
                                    @break
                                @case('shipping-status')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['shipments']) }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['shipping_revenue'], 2) }}</td>
                                    @break
                                @case('shipping-courier')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['shipments']) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row['delivered']) }}</td>
                                    <td class="px-4 py-3">{{ $row['avg_delivery_days'] !== null ? number_format($row['avg_delivery_days'], 1).' days' : '—' }}</td>
                                    @break
                                @case('shipping-fulfillment')
                                    <td class="px-4 py-3">{{ $row['label'] }}</td>
                                    <td class="px-4 py-3">{{ $row['courier'] }}</td>
                                    <td class="px-4 py-3">{{ $row['tracking'] }}</td>
                                    <td class="px-4 py-3">{{ $row['status'] }}</td>
                                    <td class="px-4 py-3">{{ $row['shipped_at'] }}</td>
                                    <td class="px-4 py-3">{{ $row['delivered_at'] }}</td>
                                    <td class="px-4 py-3">{{ $currency }} {{ number_format($row['shipping_fee'], 2) }}</td>
                                    @break
                            @endswitch
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($tableHeaders) }}" class="px-4 py-8 text-center text-gray-500">No data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartConfig = @json($chart);
            const ctx = document.getElementById('reportChart');

            if (!ctx || !chartConfig.labels?.length) {
                return;
            }

            const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#14b8a6'];

            new Chart(ctx, {
                type: chartConfig.type || 'bar',
                data: {
                    labels: chartConfig.labels,
                    datasets: [{
                        label: chartConfig.label || 'Value',
                        data: chartConfig.values,
                        backgroundColor: chartConfig.type === 'line'
                            ? 'rgba(99, 102, 241, 0.15)'
                            : colors.map(c => c + 'cc'),
                        borderColor: chartConfig.type === 'line' ? '#6366f1' : colors,
                        borderWidth: chartConfig.type === 'line' ? 2 : 1,
                        fill: chartConfig.type === 'line',
                        tension: 0.3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: chartConfig.type === 'doughnut' },
                    },
                    scales: chartConfig.type === 'doughnut' ? {} : {
                        y: { beginAtZero: true },
                    },
                },
            });
        });
    </script>
@endpush
