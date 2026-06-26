@extends('layouts.storefront')

@section('title', 'My Dashboard — '.config('app.name'))
@section('meta_description', 'Manage your orders and account at '.config('app.name').'.')

@section('content')
    <div class="ds-container ds-section">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1 space-y-6 min-w-0">
                <div>
                    <h1 class="ds-heading-2">Welcome back, {{ auth()->user()->name }}</h1>
                    <p class="ds-body mt-2">Overview of your orders and account activity.</p>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="ds-card ds-card-body">
                        <p class="ds-body-sm text-ink-500">Total orders</p>
                        <p class="text-2xl font-bold text-ink mt-1">{{ $stats['total_orders'] }}</p>
                    </div>
                    <div class="ds-card ds-card-body">
                        <p class="ds-body-sm text-ink-500">Pending</p>
                        <p class="text-2xl font-bold text-ink mt-1">{{ $stats['pending_orders'] }}</p>
                    </div>
                    <div class="ds-card ds-card-body">
                        <p class="ds-body-sm text-ink-500">Processing</p>
                        <p class="text-2xl font-bold text-ink mt-1">{{ $stats['processing_orders'] }}</p>
                    </div>
                    <div class="ds-card ds-card-body">
                        <p class="ds-body-sm text-ink-500">Delivered</p>
                        <p class="text-2xl font-bold text-ink mt-1">{{ $stats['delivered_orders'] }}</p>
                    </div>
                    <div class="ds-card ds-card-body col-span-2 lg:col-span-2">
                        <p class="ds-body-sm text-ink-500">Total amount spent</p>
                        <p class="text-2xl font-bold text-ink mt-1"><x-money :amount="$stats['total_spent']" /></p>
                    </div>
                </div>

                <div class="ds-card overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-ink-100">
                        <h2 class="ds-heading-4">Recent orders</h2>
                        <a href="{{ route('shop.account.orders.index') }}" class="ds-link ds-body-sm">View all</a>
                    </div>
                    @if ($recentOrders->isEmpty())
                        <p class="p-5 ds-body-sm text-ink-500">You haven't placed any orders yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full ds-body-sm">
                                <thead class="bg-surface-muted text-left text-ink-500">
                                    <tr>
                                        <th class="px-5 py-3 font-medium">Order</th>
                                        <th class="px-5 py-3 font-medium hidden sm:table-cell">Date</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3 font-medium text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-100">
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td class="px-5 py-4">
                                                <a href="{{ route('shop.account.orders.show', $order) }}" class="font-medium text-ink hover:underline">{{ $order->order_number }}</a>
                                            </td>
                                            <td class="px-5 py-4 hidden sm:table-cell text-ink-600">{{ $order->created_at?->format('M j, Y') }}</td>
                                            <td class="px-5 py-4"><x-order-status-badge :status="$order->status" /></td>
                                            <td class="px-5 py-4 text-right font-medium"><x-money :amount="$order->grand_total" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
