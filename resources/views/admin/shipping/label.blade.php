@extends('layouts.print')

@section('title', 'Shipping Label — '.$shipment->order?->order_number)

@section('content')
    @php
        $order = $shipment->order;
        $address = $order?->shippingAddress;
        $addressText = $address
            ? collect([$address->line1, $address->line2, $address->city, $address->state, $address->postal_code, $address->country])->filter()->implode(', ')
            : ($order?->notes ?? 'Address on file');
    @endphp

    <div class="label-box">
        <p class="muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Ship To</p>
        <h2 style="margin-bottom: 4px;">{{ $order?->customer_name }}</h2>
        <p>{{ $order?->customer_phone }}</p>
        <p style="margin: 12px 0;">{{ $addressText }}</p>

        <hr style="border: none; border-top: 1px solid #d1d5db; margin: 16px 0;">

        <div class="grid-2">
            <div>
                <p class="muted">Order</p>
                <strong>{{ $order?->order_number }}</strong>
            </div>
            <div>
                <p class="muted">Courier</p>
                <strong>{{ $shipment->courier_name }}</strong>
            </div>
        </div>

        @if ($shipment->tracking_number)
            <p class="muted" style="margin-top: 16px;">Tracking Number</p>
            <div class="barcode">{{ $shipment->tracking_number }}</div>
        @endif

        <p class="muted" style="margin-top: 16px;">Items: {{ $order?->items->sum('quantity') ?? 0 }} · {{ config('shop.currency_symbol') }} {{ number_format($order?->grand_total ?? 0, 2) }}</p>
    </div>
@endsection
