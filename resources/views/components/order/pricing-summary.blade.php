@props([
    'record',
    'finalLabel' => 'Final Total',
    'showCharges' => true,
])

@php
    $order = $record->order ?? null;
    $finalTotal = $record->grand_total ?? $record->total;
    $offerDiscount = (float) ($record->offer_discount_total ?? $order?->offer_discount_total ?? 0);
    $shippingDiscount = (float) ($record->shipping_discount_total ?? $order?->shipping_discount_total ?? 0);
    $shippingCharged = (float) ($record->shipping_total ?? 0);
    // Gross shipping before waiver — so Shipping − Shipping Discount nets correctly in the breakdown.
    $shippingGross = round($shippingCharged + $shippingDiscount, 2);
    $discountTotal = (float) ($record->discount_total ?? $order?->discount_total ?? 0);
    $otherDiscount = max(0, round($discountTotal - $offerDiscount, 2));
    $couponCode = $record->coupon_code ?? $order?->coupon_code;
    $serviceCharge = (float) ($record->service_charge_total ?? $order?->service_charge_total ?? 0);
    $handlingCharge = (float) ($record->handling_charge_total ?? $order?->handling_charge_total ?? 0);
    $taxTotal = (float) ($record->tax_total ?? 0);
    $taxLabel = $record->tax_label ?? $order?->tax_label ?? 'Tax';
@endphp

<dl {{ $attributes->class(['order-pricing-summary space-y-2']) }}>
    <div class="flex justify-between gap-4">
        <dt>Subtotal</dt>
        <dd><x-money :amount="$record->subtotal" /></dd>
    </div>

    @if ($offerDiscount > 0)
        <div class="flex justify-between gap-4 text-success">
            <dt>Offer Discount</dt>
            <dd>- <x-money :amount="$offerDiscount" /></dd>
        </div>
    @endif

    @if ($otherDiscount > 0)
        <div class="flex justify-between gap-4 text-success">
            <dt>
                Discount
                @if (! empty($couponCode))
                    ({{ $couponCode }})
                @endif
            </dt>
            <dd>- <x-money :amount="$otherDiscount" /></dd>
        </div>
    @elseif ($discountTotal > 0 && $offerDiscount <= 0)
        <div class="flex justify-between gap-4 text-success">
            <dt>
                Discount
                @if (! empty($couponCode))
                    ({{ $couponCode }})
                @endif
            </dt>
            <dd>- <x-money :amount="$discountTotal" /></dd>
        </div>
    @endif

    <div class="flex justify-between gap-4">
        <dt>Shipping</dt>
        <dd>
            @if ($shippingGross <= 0)
                <span class="text-success">Free</span>
            @else
                <x-money :amount="$shippingGross" />
            @endif
        </dd>
    </div>

    @if ($shippingDiscount > 0)
        <div class="flex justify-between gap-4 text-success">
            <dt>Shipping Discount</dt>
            <dd>- <x-money :amount="$shippingDiscount" /></dd>
        </div>
    @endif

    @if ($showCharges && $serviceCharge > 0)
        <div class="flex justify-between gap-4">
            <dt>Service charge</dt>
            <dd><x-money :amount="$serviceCharge" /></dd>
        </div>
    @endif

    @if ($showCharges && $handlingCharge > 0)
        <div class="flex justify-between gap-4">
            <dt>Handling charge</dt>
            <dd><x-money :amount="$handlingCharge" /></dd>
        </div>
    @endif

    @if ($taxTotal > 0)
        <div class="flex justify-between gap-4">
            <dt>{{ $taxLabel }}</dt>
            <dd><x-money :amount="$taxTotal" /></dd>
        </div>
    @endif

    <div class="flex justify-between gap-4 font-semibold pt-2 border-t border-ink-100">
        <dt>{{ $finalLabel }}</dt>
        <dd><x-money :amount="$finalTotal" /></dd>
    </div>
</dl>
