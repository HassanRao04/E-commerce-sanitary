@props(['item'])

@php
    $product = $item->product;
    $offers = $product?->offers_enabled ? ($product->offers ?? collect()) : collect();
    $pipeOptions = $product?->pipe_length_enabled ? ($product->pipeLengthOptions ?? collect()) : collect();
    $optionTitle = $product?->resolvedOptionTitle() ?? 'Options';
@endphp

<article class="cart-item" data-cart-item="{{ $item->id }}">
    <a href="{{ route('shop.products.show', $item->product) }}" class="cart-item__media">
        <img
            src="{{ $item->product->primary_image_url }}"
            alt="{{ $item->product->name }}"
            width="96"
            height="96"
            loading="lazy"
            decoding="async"
        >
    </a>

    <div class="cart-item__body">
        <div class="cart-item__header">
            <div class="min-w-0">
                <a href="{{ route('shop.products.show', $item->product) }}" class="cart-item__title">{{ $item->product->name }}</a>
                <x-storefront.variant-options :item="$item" class="cart-item__variant" />
                <p class="cart-item__unit-price" data-unit-price><x-money :amount="$item->unit_price" /> each</p>
            </div>

            <p class="cart-item__line-total" data-line-total>
                <x-money :amount="$item->unit_price * $item->quantity" />
            </p>
        </div>

        @if ($offers->isNotEmpty())
            <div class="cart-item__offers">
                <label class="cart-item__option-label" for="cart-offer-{{ $item->id }}">Offer</label>
                <select
                    id="cart-offer-{{ $item->id }}"
                    class="cart-item__select"
                    data-cart-offer
                >
                    <option value="" data-buy-quantity="1" @selected(! $item->product_offer_id)>Buy 1</option>
                    @foreach ($offers as $offer)
                        <option
                            value="{{ $offer->id }}"
                            data-buy-quantity="{{ $offer->buy_quantity }}"
                            @selected((int) $item->product_offer_id === (int) $offer->id)
                        >
                            Buy {{ $offer->buy_quantity }}
                            @if ((float) $offer->discount_percent > 0)
                                — {{ rtrim(rtrim(number_format((float) $offer->discount_percent, 2), '0'), '.') }}% OFF
                            @endif
                            @if ($offer->free_shipping)
                                + Free Shipping
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($pipeOptions->isNotEmpty())
            <div class="cart-item__pipe">
                <label class="cart-item__option-label" for="cart-pipe-{{ $item->id }}">{{ $optionTitle }}</label>
                <select
                    id="cart-pipe-{{ $item->id }}"
                    class="cart-item__select"
                    data-cart-pipe
                >
                    @foreach ($pipeOptions as $option)
                        <option value="{{ $option->id }}" @selected((int) $item->pipe_length_option_id === (int) $option->id)>
                            {{ $option->label }}
                            @if ((float) $option->additional_price > 0)
                                (+ {{ config('shop.currency_symbol') }} {{ number_format((float) $option->additional_price, 2) }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="cart-item__actions">
            <div class="product-qty" data-qty-control>
                <button type="button" class="product-qty__btn" data-qty-decrease aria-label="Decrease quantity">−</button>
                <input
                    type="number"
                    value="{{ $item->quantity }}"
                    min="1"
                    max="99"
                    class="product-qty__input"
                    data-qty-input
                    aria-label="Quantity for {{ $item->product->name }}"
                >
                <button type="button" class="product-qty__btn" data-qty-increase aria-label="Increase quantity">+</button>
            </div>

            <button type="button" class="cart-item__remove" data-remove-item aria-label="Remove {{ $item->product->name }}">
                Remove
            </button>
        </div>
    </div>
</article>
