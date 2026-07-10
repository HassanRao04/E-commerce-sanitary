@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    @php
        $statuses = app(\App\Services\OrderWorkflowService::class)->active();
    @endphp

    @include('admin.partials.page-header', ['title' => 'Orders'])

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        @foreach ($statuses as $status)
            @php $count = $statusCounts[$status->slug] ?? 0; @endphp
            <a href="{{ route('admin.orders.index', ['status' => $status->slug]) }}"
               class="rounded-lg border bg-white p-3 hover:border-indigo-300 {{ request('status') === $status->slug ? 'ring-2 ring-indigo-500 border-indigo-300' : 'border-gray-200' }}">
                <p class="text-xs text-gray-500">{{ $status->name }}</p>
                <p class="text-xl font-semibold mt-1">{{ number_format($count) }}</p>
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Order #, customer, email..."
                class="md:col-span-2 rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <select name="status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All order statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->slug }}" @selected(request('status') === $status->slug)>{{ $status->name }}</option>
                @endforeach
            </select>
            <select name="payment_status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All payment statuses</option>
                @foreach (\App\Enums\PaymentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('payment_status') === $status->value)>{{ str($status->value)->headline()->replace('_', ' ') }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border-gray-300 shadow-sm" title="From date">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border-gray-300 shadow-sm" title="To date">
            <button type="submit" class="md:col-span-6 px-4 py-2 bg-slate-900 text-white rounded-md text-sm hover:bg-slate-800">Search & Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Order</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Customer</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Total</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Payment</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $order->order_number }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $order->customer_name }}</div>
                            <div class="text-gray-500 text-xs">{{ $order->customer_email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $order->formatted_grand_total }}</td>
                        <td class="px-4 py-3"><x-order-status-badge :status="$order->status" /></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs bg-blue-50 text-blue-700">{{ str($order->payment_status->value)->headline() }}</span></td>
                        <td class="px-4 py-3 text-gray-600">{{ $order->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('view', $order)
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-slate-700 hover:underline">View</a>
                                <a href="{{ route('admin.orders.track', $order) }}" class="text-indigo-600 hover:underline">Track</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($orders->hasPages())
            <div class="px-4 py-3 border-t">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
