@props([
    'product',
    'inWishlist' => false,
])

@php
    $variants = $product->variants->map(fn ($variant) => [
        'id' => $variant->id,
        'label' => $variant->variant_name ?: trim(collect([$variant->color_name, $variant->finish, $variant->size])->filter()->join(' · ')) ?: $variant->sku,
        'sku' => $variant->sku,
        'price' => (float) $variant->price,
        'salePrice' => $variant->sale_price ? (float) $variant->sale_price : null,
        'effectivePrice' => (float) $variant->effective_price,
        'priceFormatted' => config('shop.currency_symbol').' '.number_format((float) $variant->effective_price, 2),
        'comparePriceFormatted' => $variant->sale_price
            ? config('shop.currency_symbol').' '.number_format((float) $variant->price, 2)
            : null,
        'stock' => (int) $variant->stock_quantity,
        'colorHex' => $variant->color_hex,
        'colorName' => $variant->color_name,
        'purchasable' => $variant->stock_quantity > 0,
    ])->values();

    $defaultVariant = $variants->firstWhere('id', $product->default_variant_id) ?? $variants->first();
    $lowThreshold = (int) config('shop.low_stock_threshold', 5);
@endphp

<div class="product-purchase" x-data="productPurchase(@js([
    'variants' => $variants,
    'defaultVariantId' => $defaultVariant['id'] ?? null,
    'lowStockThreshold' => $lowThreshold,
    'inWishlist' => $inWishlist,
    'productId' => $product->id,
        'routes' => [
        'cartStore' => route('shop.cart.store'),
        'wishlistStore' => route('shop.wishlist.store'),
        'wishlistRemove' => url('/wishlist'),
    ],
]))">
    <form method="POST" action="{{ route('shop.cart.store') }}" class="product-purchase__form" @submit="prepareSubmit($event)">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="product_variant_id" :value="selectedVariantId">
        <input type="hidden" name="buy_now" :value="buyNow ? 1 : 0">

        @if ($variants->count() > 1)
            <div class="product-purchase__section">
                <div class="product-purchase__label-row">
                    <span class="product-purchase__label">Select option</span>
                    <span class="product-purchase__selected" x-text="selectedVariant?.label"></span>
                </div>

                <div class="product-purchase__variants">
                    <template x-for="variant in variants" :key="variant.id">
                        <button
                            type="button"
                            class="product-variant-btn"
                            :class="{
                                'is-active': selectedVariantId === variant.id,
                                'is-swatch': !!variant.colorHex,
                                'is-disabled': !variant.purchasable,
                            }"
                            @click="selectVariant(variant.id)"
                            :disabled="!variant.purchasable"
                            :title="variant.label"
                        >
                            <span
                                x-show="variant.colorHex"
                                class="product-variant-btn__swatch"
                                :style="`background-color: ${variant.colorHex}`"
                            ></span>
                            <span x-show="!variant.colorHex" x-text="variant.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        @else
            <input type="hidden" name="product_variant_id" value="{{ $variants->first()['id'] ?? '' }}">
        @endif

        <div class="product-purchase__price">
            <span class="product-purchase__price-current" x-text="selectedVariant?.priceFormatted"></span>
            <span class="product-purchase__price-compare" x-show="selectedVariant?.comparePriceFormatted" x-text="selectedVariant?.comparePriceFormatted"></span>
        </div>

        <div class="product-purchase__meta">
            <span class="product-purchase__sku" x-show="selectedVariant?.sku">
                SKU: <span x-text="selectedVariant?.sku"></span>
            </span>
            <span class="product-purchase__stock" :class="stockBadgeClass()" x-text="stockLabel()"></span>
        </div>

        <div class="product-purchase__quantity">
            <span class="product-purchase__label">Quantity</span>
            <div class="product-qty">
                <button type="button" class="product-qty__btn" @click="decrementQty()" aria-label="Decrease quantity">−</button>
                <input type="number" name="quantity" x-model.number="quantity" min="1" max="99" class="product-qty__input" aria-label="Quantity">
                <button type="button" class="product-qty__btn" @click="incrementQty()" aria-label="Increase quantity">+</button>
            </div>
        </div>

        @error('quantity')<p class="ds-error-text">{{ $message }}</p>@enderror
        @error('product')<p class="ds-error-text">{{ $message }}</p>@enderror
        @error('product_variant_id')<p class="ds-error-text">{{ $message }}</p>@enderror

        <div class="product-purchase__actions">
            <button
                type="submit"
                class="ds-btn-primary ds-btn-lg product-purchase__btn"
                :disabled="!canPurchase || submitting"
                @click="buyNow = false"
            >
                <span x-show="!submitting">Add to cart</span>
                <span x-show="submitting && !buyNow" x-cloak>Adding…</span>
            </button>
            <button
                type="submit"
                class="ds-btn-secondary ds-btn-lg product-purchase__btn"
                :disabled="!canPurchase || submitting"
                @click="buyNow = true"
            >
                <span x-show="!submitting">Buy now</span>
                <span x-show="submitting && buyNow" x-cloak>Processing…</span>
            </button>
            <button
                type="button"
                class="product-purchase__wishlist ds-btn-icon !h-12 !w-12"
                :class="{ 'is-active': wishlisted }"
                @click="toggleWishlist()"
                :disabled="togglingWishlist"
                aria-label="Toggle wishlist"
            >
                <svg x-show="!wishlisted" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <svg x-show="wishlisted" x-cloak class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </button>
        </div>

        <ul class="product-purchase__benefits">
            <li>Secure checkout &amp; multiple payment options</li>
            <li>Fast delivery across Pakistan</li>
            <li>Genuine products with warranty support</li>
        </ul>
    </form>
</div>
