@extends('layouts.storefront')

@section('title', 'Write reviews — '.config('app.name'))

@section('content')
    <div class="ds-container ds-section">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => route('shop.account.dashboard')],
            ['label' => 'My Orders', 'url' => route('shop.account.orders.index')],
            ['label' => $order->order_number, 'url' => route('shop.account.orders.show', $order)],
            ['label' => 'Write reviews', 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1">
                <h1 class="ds-heading-2 mb-2">Write a review</h1>
                <p class="ds-body-sm text-ink-500 mb-6">Choose a product from order {{ $order->order_number }} to review.</p>

                <div class="space-y-3">
                    @foreach ($reviewableItems as $item)
                        <div class="ds-card ds-card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="font-medium text-ink">{{ $item->product_name }}</p>
                                <p class="ds-body-sm text-ink-500">{{ $item->sku }} · Qty {{ $item->quantity }}</p>
                            </div>
                            <a href="{{ route('shop.account.orders.review.create', [$order, $item]) }}" class="ds-btn-primary !text-sm">Write review</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
