@extends('layouts.print')

@section('title', 'Invoice '.$invoice->invoice_number)

@section('content')
    <div class="grid-2" style="margin-bottom: 24px;">
        <div>
            <h1>{{ config('app.name', 'Sanitary Store') }}</h1>
            <p class="muted">Tax Invoice</p>
        </div>
        <div class="text-right">
            <h2>{{ $invoice->invoice_number }}</h2>
            <p class="muted">Order: {{ $order->order_number }}</p>
            <p class="muted">Date: {{ $invoice->issued_at?->format('M j, Y') ?? now()->format('M j, Y') }}</p>
        </div>
    </div>

    <div class="grid-2" style="margin-bottom: 24px;">
        <div>
            <h3>Bill To</h3>
            <p><strong>{{ $invoice->billing_name }}</strong></p>
            <p>{{ $invoice->billing_email }}</p>
            <p class="muted">{{ $invoice->billing_address ?? '—' }}</p>
        </div>
        <div>
            <h3>Order Details</h3>
            <p>Payment: {{ str($order->payment_status->value)->headline() }}</p>
            <p>Status: {{ str($order->status->value)->headline() }}</p>
            @if ($order->coupon_code)
                <p>Coupon: {{ $order->coupon_code }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>SKU</th>
                <th>Qty</th>
                <th class="text-right">Unit</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ trim($item->product_name.' '.($item->variant_name ?? '')) }}</td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td class="text-right">{{ config('shop.currency_symbol') }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ config('shop.currency_symbol') }} {{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals" style="max-width: 320px; margin-left: auto;">
        <div><span>Subtotal</span><span>{{ config('shop.currency_symbol') }} {{ number_format($invoice->subtotal, 2) }}</span></div>
        @if ($invoice->discount_total > 0)
            <div><span>Discount</span><span>- {{ config('shop.currency_symbol') }} {{ number_format($invoice->discount_total, 2) }}</span></div>
        @endif
        @if ($invoice->shipping_total > 0)
            <div><span>Shipping</span><span>{{ config('shop.currency_symbol') }} {{ number_format($invoice->shipping_total, 2) }}</span></div>
        @endif
        @if ($invoice->tax_total > 0)
            <div><span>Tax</span><span>{{ config('shop.currency_symbol') }} {{ number_format($invoice->tax_total, 2) }}</span></div>
        @endif
        <div class="grand"><span>Total</span><span>{{ $invoice->formatted_total }}</span></div>
    </div>
@endsection
