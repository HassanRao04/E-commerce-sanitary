@props(['item'])

<article class="cart-item" data-cart-item="{{ $item->id }}">
    <a href="{{ route('shop.products.show', $item->product) }}" class="cart-item__media">
        <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}" loading="lazy">
    </a>

    <div class="cart-item__body">
        <div class="cart-item__header">
            <div class="min-w-0">
                <a href="{{ route('shop.products.show', $item->product) }}" class="cart-item__title">{{ $item->product->name }}</a>
                <x-storefront.variant-options :item="$item" class="cart-item__variant" />
                <p class="cart-item__unit-price"><x-money :amount="$item->unit_price" /> each</p>
            </div>

            <p class="cart-item__line-total" data-line-total>
                <x-money :amount="$item->unit_price * $item->quantity" />
            </p>
        </div>

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
