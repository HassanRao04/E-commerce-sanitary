@props([
    'cart',
    'totals',
    'pricing' => [],
    'showCoupon' => true,
    'showItems' => true,
    'sticky' => true,
])

<div @class(['order-summary', 'order-summary--sticky' => $sticky])>
    <h2 class="order-summary__title">Order summary</h2>

    @if ($showItems)
        <ul class="order-summary__items">
            @foreach ($cart->items as $item)
                <li class="order-summary__item">
                    <img src="{{ $item->product->primary_image_url }}" alt="" loading="lazy">
                    <div class="order-summary__item-body">
                        <p class="order-summary__item-name">{{ $item->product->name }}</p>
                        @if ($item->productVariant?->variant_name)
                            <p class="order-summary__item-meta">{{ $item->productVariant->variant_name }}</p>
                        @endif
                        <p class="order-summary__item-meta">Qty {{ $item->quantity }} × <x-money :amount="$item->unit_price" /></p>
                    </div>
                    <span class="order-summary__item-price"><x-money :amount="$item->unit_price * $item->quantity" /></span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($showCoupon)
        <div class="order-summary__coupon">
            <p class="order-summary__coupon-label">Coupon code</p>
            @if ($cart->coupon)
                <div class="order-summary__coupon-applied">
                    <span class="order-summary__coupon-badge">{{ $cart->coupon->code }}</span>
                    <form action="{{ route('shop.cart.coupon.remove') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="order-summary__coupon-remove">Remove</button>
                    </form>
                </div>
            @else
                <form action="{{ route('shop.cart.coupon.apply') }}" method="POST" class="order-summary__coupon-form">
                    @csrf
                    <input type="text" name="code" placeholder="Enter code" class="order-summary__coupon-input" autocomplete="off">
                    <button type="submit" class="ds-btn-secondary ds-btn-sm">Apply</button>
                </form>
                @error('code')<p class="ds-error-text mt-2">{{ $message }}</p>@enderror
            @endif
        </div>
    @endif

    <dl class="order-summary__totals" data-order-totals>
        <div class="order-summary__row">
            <dt>Subtotal</dt>
            <dd data-total-subtotal><x-money :amount="$totals['subtotal']" /></dd>
        </div>

        @if ($totals['discount'] > 0)
            <div class="order-summary__row order-summary__row--discount">
                <dt>
                    Discount
                    @if (! empty($pricing['coupon_code']))
                        ({{ $pricing['coupon_code'] }})
                    @endif
                </dt>
                <dd data-total-discount>- <x-money :amount="$totals['discount']" /></dd>
            </div>
        @endif

        <div class="order-summary__row">
            <dt>Shipping</dt>
            <dd data-total-shipping>
                @if ($totals['shipping'] <= 0 && ! empty($pricing['qualifies_for_free_shipping']))
                    <span class="order-summary__free">Free</span>
                @else
                    <x-money :amount="$totals['shipping']" />
                @endif
            </dd>
        </div>

        @if (! empty($pricing['free_shipping_threshold']) && empty($pricing['qualifies_for_free_shipping']))
            <p class="order-summary__hint">
                Free shipping on orders over <x-money :amount="$pricing['free_shipping_threshold']" />
            </p>
        @endif

        @if ($totals['tax'] > 0)
            <div class="order-summary__row">
                <dt>
                    {{ config('shop.tax_label', 'Tax') }}
                    @if (! empty($pricing['tax_rate']))
                        ({{ rtrim(rtrim(number_format($pricing['tax_rate'], 2), '0'), '.') }}%)
                    @endif
                </dt>
                <dd data-total-tax><x-money :amount="$totals['tax']" /></dd>
            </div>
        @endif

        <div class="order-summary__row order-summary__row--total">
            <dt>Total</dt>
            <dd data-total-grand><x-money :amount="$totals['grand_total']" /></dd>
        </div>
    </dl>

    {{ $slot ?? '' }}

    @error('cart')<p class="ds-error-text mt-4">{{ $message }}</p>@enderror
</div>
