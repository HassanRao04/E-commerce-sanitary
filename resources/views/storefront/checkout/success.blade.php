@extends('layouts.storefront')

@section('title', 'Order confirmed — '.config('app.name'))

@section('content')
    <div class="commerce-page commerce-page--success">
        <div class="ds-container ds-section-tight max-w-3xl">
            <x-storefront.checkout-progress current="confirmation" />

            <div class="text-center anim-gpu" data-aos="fade-up">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-success-soft text-success text-2xl mb-6">✓</div>
                <h1 class="ds-heading-1">Thank you for your order!</h1>
                <p class="ds-body mt-3">Your order has been placed successfully.</p>
            </div>

            <div class="mt-8 ds-card ds-card-body space-y-4">
                <dl class="grid sm:grid-cols-2 gap-4 ds-body-sm">
                    <div>
                        <dt class="text-ink-500">Order number</dt>
                        <dd class="font-semibold text-lg text-ink">{{ $order->order_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Order date</dt>
                        <dd class="font-medium">{{ $order->created_at?->format('M j, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Payment method</dt>
                        <dd class="font-medium">{{ str($order->payment_method->name)->headline() }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Estimated delivery</dt>
                        <dd class="font-medium">{{ $order->estimated_delivery_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-6 ds-card ds-card-body">
                <h2 class="ds-heading-4 mb-4">Order summary</h2>
                <ul class="space-y-2 ds-body-sm">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-4">
                            <div class="min-w-0">
                                <p>{{ $item->product_name }} × {{ $item->quantity }}</p>
                                <x-storefront.variant-options :item="$item" class="text-ink-500 text-xs mt-0.5" />
                            </div>
                            <span class="font-medium shrink-0"><x-money :amount="$item->total" /></span>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 ds-body-sm mt-4 pt-4 ds-divider">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd><x-money :amount="$order->subtotal" /></dd></div>
                    @if ($order->discount_total > 0)
                        <div class="flex justify-between text-success">
                            <dt>
                                Discount
                                @if ($order->coupon_code)
                                    ({{ $order->coupon_code }})
                                @endif
                            </dt>
                            <dd>- <x-money :amount="$order->discount_total" /></dd>
                        </div>
                    @endif
                    <div class="flex justify-between"><dt>Shipping</dt><dd><x-money :amount="$order->shipping_total" /></dd></div>
                    @if ($order->service_charge_total > 0)
                        <div class="flex justify-between"><dt>Service charge</dt><dd><x-money :amount="$order->service_charge_total" /></dd></div>
                    @endif
                    @if ($order->handling_charge_total > 0)
                        <div class="flex justify-between"><dt>Handling charge</dt><dd><x-money :amount="$order->handling_charge_total" /></dd></div>
                    @endif
                    @if ($order->tax_total > 0)
                        <div class="flex justify-between"><dt>{{ $order->tax_label }}</dt><dd><x-money :amount="$order->tax_total" /></dd></div>
                    @endif
                    <div class="flex justify-between font-semibold text-base pt-2 border-t border-ink-100"><dt>Total</dt><dd><x-money :amount="$order->grand_total" /></dd></div>
                </dl>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('shop.account.orders.index') }}" class="ds-btn-primary text-center">View my orders</a>
                <a href="{{ route('shop.products.index') }}" class="ds-btn-secondary text-center">Continue shopping</a>
                @if ($order->payment_method->value === 'bank_transfer')
                    <a href="{{ route('shop.payment.show', $order) }}" class="ds-btn-secondary text-center">Payment instructions</a>
                @endif
            </div>
        </div>
    </div>
@endsection
