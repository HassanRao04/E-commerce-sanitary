@extends('layouts.storefront')

@section('title', 'My Orders — '.config('app.name'))

@section('content')
    <div class="ds-container ds-section">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => route('shop.account.dashboard')],
            ['label' => 'My Orders', 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1 min-w-0">
                <h1 class="ds-heading-2 mb-6">My orders</h1>

                @if ($orders->isEmpty())
                    <div class="ds-card ds-card-body text-center py-12">
                        <p class="ds-body text-ink-600">No orders yet.</p>
                        <a href="{{ route('shop.products.index') }}" class="ds-btn-primary mt-4 inline-flex">Start shopping</a>
                    </div>
                @else
                    <div class="ds-card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full ds-body-sm min-w-[720px]">
                                <thead class="bg-surface-muted text-left text-ink-500">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">Order number</th>
                                        <th class="px-4 py-3 font-medium">Date</th>
                                        <th class="px-4 py-3 font-medium">Status</th>
                                        <th class="px-4 py-3 font-medium">Total</th>
                                        <th class="px-4 py-3 font-medium">Payment</th>
                                        <th class="px-4 py-3 font-medium">Tracking</th>
                                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-100">
                                    @foreach ($orders as $order)
                                        <tr class="hover:bg-surface-muted/50">
                                            <td class="px-4 py-4 font-medium text-ink">{{ $order->order_number }}</td>
                                            <td class="px-4 py-4 text-ink-600 whitespace-nowrap">{{ $order->created_at?->format('M j, Y') }}</td>
                                            <td class="px-4 py-4"><x-order-status-badge :status="$order->status" /></td>
                                            <td class="px-4 py-4 font-medium whitespace-nowrap"><x-money :amount="$order->grand_total" /></td>
                                            <td class="px-4 py-4 capitalize text-ink-600">{{ str($order->payment_status->value)->replace('_', ' ') }}</td>
                                            <td class="px-4 py-4 text-ink-600">{{ $order->tracking_number ?? '—' }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <a href="{{ route('shop.account.orders.show', $order) }}" class="ds-btn-ghost !px-2 !py-1 !text-xs">View</a>
                                                    <a href="{{ route('shop.account.orders.track', $order) }}" class="ds-btn-ghost !px-2 !py-1 !text-xs">Track</a>
                                                    <a href="{{ route('shop.account.orders.invoice', $order) }}" target="_blank" class="ds-btn-ghost !px-2 !py-1 !text-xs">Invoice</a>
                                                    @if ($reviewsEnabled && ($reviewStates[$order->id]['can_review'] ?? false))
                                                        <a href="{{ route('shop.account.orders.review', $order) }}" class="ds-btn-primary !px-2 !py-1 !text-xs">Write review</a>
                                                    @elseif ($reviewsEnabled && ($reviewStates[$order->id]['has_review'] ?? false))
                                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Review submitted</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-6">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
