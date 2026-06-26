@extends('layouts.storefront')

@section('title', 'Track '.$order->order_number.' — '.config('app.name'))

@section('content')
    <div class="ds-container ds-section">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => route('shop.account.dashboard')],
            ['label' => 'My Orders', 'url' => route('shop.account.orders.index')],
            ['label' => $order->order_number, 'url' => route('shop.account.orders.show', $order)],
            ['label' => 'Track', 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1 space-y-6 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="ds-heading-2">Track order</h1>
                        <p class="ds-body-sm text-ink-500 mt-1">{{ $order->order_number }} · Placed {{ $order->created_at?->format('M j, Y') }}</p>
                    </div>
                    <x-order-status-badge :status="$order->status" />
                </div>

                <div class="ds-card ds-card-body">
                    <x-storefront.order-progress :order="$order" />
                </div>

                @foreach ($order->shipments as $shipment)
                    <div class="ds-card ds-card-body">
                        <h2 class="ds-heading-4">{{ $shipment->courier_name ?? 'Shipment' }}</h2>
                        @if ($shipment->tracking_number)
                            <p class="ds-body-sm mt-2">Tracking number: <strong>{{ $shipment->tracking_number }}</strong></p>
                        @endif
                        @if ($shipment->tracking_url)
                            <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener" class="ds-link ds-body-sm inline-flex mt-2">Open courier tracking</a>
                        @endif
                    </div>
                @endforeach

                @if ($order->statusHistories->isNotEmpty())
                    <div class="ds-card ds-card-body">
                        <h2 class="ds-heading-4 mb-4">Status updates</h2>
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

                <a href="{{ route('shop.account.orders.show', $order) }}" class="ds-btn-secondary inline-flex">Back to order details</a>
            </div>
        </div>
    </div>
@endsection
