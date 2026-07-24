@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' — '.config('app.name'))

@section('content')
    <div class="ds-container ds-section">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => route('shop.account.dashboard')],
            ['label' => 'My Orders', 'url' => route('shop.account.orders.index')],
            ['label' => $order->order_number, 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1 space-y-6 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 class="ds-heading-2">{{ $order->order_number }}</h1>
                        <p class="ds-body-sm text-ink-500 mt-1">Placed {{ $order->created_at?->format('M j, Y H:i') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-order-status-badge :status="$order->status" />
                        <a href="{{ route('shop.account.orders.track', $order) }}" class="ds-btn-secondary !text-sm">Track order</a>
                        <a href="{{ route('shop.account.orders.invoice', $order) }}" target="_blank" class="ds-btn-secondary !text-sm">Download invoice</a>
                        @if ($reviewsEnabled && $orderReviewEligible && $reviewableItems->isNotEmpty())
                            <a href="{{ route('shop.account.orders.review', $order) }}" class="ds-btn-primary !text-sm">Write review</a>
                        @elseif ($reviewsEnabled && $orderReviewEligible && $order->items->contains(fn ($item) => $item->review))
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700">Review submitted</span>
                        @endif
                    </div>
                </div>

                <div class="ds-card ds-card-body">
                    <h2 class="ds-heading-4 mb-4">Order progress</h2>
                    <x-storefront.order-progress :order="$order" />
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="ds-card ds-card-body">
                        <h2 class="ds-heading-4 mb-3">Customer information</h2>
                        <dl class="ds-body-sm space-y-2">
                            <div><dt class="text-ink-500">Name</dt><dd class="font-medium">{{ $order->customer_name }}</dd></div>
                            <div><dt class="text-ink-500">Email</dt><dd>{{ $order->customer_email }}</dd></div>
                            <div><dt class="text-ink-500">Phone</dt><dd>{{ $order->customer_phone }}</dd></div>
                        </dl>
                    </div>
                    <div class="ds-card ds-card-body">
                        <h2 class="ds-heading-4 mb-3">Payment</h2>
                        <dl class="ds-body-sm space-y-2">
                            <div class="flex justify-between"><dt class="text-ink-500">Method</dt><dd>{{ str($order->payment_method->name)->headline() }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-500">Status</dt><dd class="capitalize">{{ str($order->payment_status->value)->replace('_', ' ') }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    @if ($order->shippingAddress)
                        <div class="ds-card ds-card-body">
                            <h2 class="ds-heading-4 mb-3">Shipping address</h2>
                            <p class="font-medium">{{ $order->shippingAddress->full_name }}</p>
                            <p class="ds-body-sm mt-1">{{ $order->shippingAddress->line1 }}@if ($order->shippingAddress->line2), {{ $order->shippingAddress->line2 }}@endif</p>
                            <p class="ds-body-sm">{{ $order->shippingAddress->city }}@if ($order->shippingAddress->state), {{ $order->shippingAddress->state }}@endif</p>
                            <p class="ds-body-sm">{{ $order->shippingAddress->country }} @if ($order->shippingAddress->postal_code){{ $order->shippingAddress->postal_code }}@endif</p>
                        </div>
                    @endif
                    @if ($order->billingAddress)
                        <div class="ds-card ds-card-body">
                            <h2 class="ds-heading-4 mb-3">Billing address</h2>
                            <p class="font-medium">{{ $order->billingAddress->full_name }}</p>
                            <p class="ds-body-sm mt-1">{{ $order->billingAddress->line1 }}</p>
                            <p class="ds-body-sm">{{ $order->billingAddress->city }}, {{ $order->billingAddress->country }}</p>
                        </div>
                    @endif
                </div>

                <div class="ds-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-ink-100">
                        <h2 class="ds-heading-4">Ordered products</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full ds-body-sm">
                            <thead class="bg-surface-muted text-left text-ink-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Product</th>
                                    <th class="px-5 py-3 font-medium">SKU</th>
                                    <th class="px-5 py-3 font-medium text-right">Qty</th>
                                    <th class="px-5 py-3 font-medium text-right">Unit price</th>
                                    <th class="px-5 py-3 font-medium text-right">Total</th>
                                    @if ($reviewsEnabled && $orderReviewEligible)
                                        <th class="px-5 py-3 font-medium text-right">Review</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td class="px-5 py-4">
                                    <p class="font-medium">{{ $item->product_name }}</p>
                                    <x-storefront.variant-options :item="$item" class="text-ink-500 text-sm mt-1" />
                                    <x-order.item-offer-meta :item="$item" class="text-ink-500" />
                                </td>
                                        <td class="px-5 py-4 text-ink-600">{{ $item->sku }}</td>
                                        <td class="px-5 py-4 text-right">{{ $item->quantity }}</td>
                                        <td class="px-5 py-4 text-right"><x-money :amount="$item->unit_price" /></td>
                                        <td class="px-5 py-4 text-right font-medium"><x-money :amount="$item->total" /></td>
                                        @if ($reviewsEnabled && $orderReviewEligible)
                                            <td class="px-5 py-4 text-right">
                                                @if ($item->review)
                                                    <span class="text-xs font-medium text-emerald-700">Review submitted</span>
                                                @elseif ($item->product_id && $reviewableItems->contains('id', $item->id))
                                                    <a href="{{ route('shop.account.orders.review.create', [$order, $item]) }}" class="ds-btn-primary !px-2 !py-1 !text-xs">Write review</a>
                                                @else
                                                    <span class="text-xs text-ink-400">—</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-4 border-t border-ink-100 bg-surface-muted/30">
                        <x-order.pricing-summary :record="$order" class="ds-body-sm max-w-sm ml-auto [&_dt]:text-ink-500" />
                    </div>
                </div>

                @if ($order->statusHistories->isNotEmpty())
                    <div class="ds-card ds-card-body">
                        <h2 class="ds-heading-4 mb-4">Order status timeline</h2>
                        <ol class="relative border-l border-ink-200 ml-3 space-y-5">
                            @foreach ($order->statusHistories->sortByDesc('created_at') as $history)
                                <li class="ml-6">
                                    <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-ink"></span>
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <x-order-status-badge :status="$history->status" />
                                        <span class="text-xs text-ink-500">{{ $history->created_at?->format('M j, Y H:i') }}</span>
                                    </div>
                                    @if ($history->note)
                                        <p class="ds-body-sm text-ink-600 mt-1">{{ $history->note }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
