@extends('layouts.storefront')

@section('title', 'Track Order — '.config('app.name'))
@section('meta_description', 'Track your order status and shipment updates at '.config('app.name').'.')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'Track Order', 'url' => null],
        ]])

        <h1 class="text-3xl font-bold mb-2">Track your order</h1>
        <p class="text-slate-600 mb-8">Enter your order details below to see the latest status.</p>

        @isset($order)
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div>
                        <p class="text-sm text-slate-500">Order number</p>
                        <p class="text-xl font-bold">{{ $order->order_number }}</p>
                    </div>
                    <x-order-status-badge :status="$order->status" />
                </div>

                <div class="grid sm:grid-cols-2 gap-4 text-sm mb-6">
                    <div><span class="text-slate-500">Placed:</span> {{ $order->created_at?->format('M j, Y') }}</div>
                    <div><span class="text-slate-500">Total:</span> <x-money :amount="$order->grand_total" /></div>
                </div>

                <div class="mb-6">
                    <x-storefront.order-progress :order="$order" />
                </div>

                @if ($order->statusHistories->isNotEmpty())
                    <h2 class="font-semibold mb-4">Order timeline</h2>
                    <ol class="relative border-l border-slate-200 ml-3 space-y-5">
                        @foreach ($order->statusHistories as $history)
                            <li class="ml-6">
                                <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-slate-900"></span>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <x-order-status-badge :status="$history->status" />
                                    <span class="text-xs text-slate-500">{{ $history->created_at?->format('M j, Y H:i') }}</span>
                                </div>
                                @if ($history->note)
                                    <p class="text-sm text-slate-600 mt-1">{{ $history->note }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif

                @foreach ($order->shipments as $shipment)
                    <div class="mt-8 pt-6 border-t border-slate-200">
                        <h3 class="font-semibold">{{ $shipment->courier_name ?? 'Shipment' }}</h3>
                        @if ($shipment->tracking_number)
                            <p class="text-sm text-slate-600 mt-1">Tracking: {{ $shipment->tracking_number }}</p>
                        @endif
                        @if ($shipment->tracking_url)
                            <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener" class="inline-flex mt-2 text-sm font-medium text-slate-900 underline">Open courier tracking</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endisset

        <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-6">
            <form action="{{ route('shop.orders.track.show') }}" method="POST" class="space-y-4">
                @csrf
                <h2 class="font-semibold">Track by order number</h2>
                <div>
                    <label for="order_number" class="block text-sm font-medium mb-1">Order number</label>
                    <input id="order_number" type="text" name="order_number" value="{{ old('order_number') }}" class="w-full rounded-lg border-slate-300" placeholder="e.g. ORD-2026-001">
                    @error('order_number')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border-slate-300">
                    @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 text-white px-5 py-2.5 text-sm font-medium hover:bg-slate-800">Track order</button>
            </form>

            <div class="border-t border-slate-200 pt-6">
                <form action="{{ route('shop.orders.track.show') }}" method="POST" class="space-y-4">
                    @csrf
                    <h2 class="font-semibold">Track by reference code</h2>
                    <div>
                        <label for="tracking_token" class="block text-sm font-medium mb-1">Tracking reference</label>
                        <input id="tracking_token" type="text" name="tracking_token" value="{{ old('tracking_token', request('tracking_token')) }}" class="w-full rounded-lg border-slate-300">
                        @error('tracking_token')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium hover:bg-slate-50">Look up</button>
                </form>
            </div>
        </div>
    </div>
@endsection
