@props([
    'product',
    'inWishlist' => false,
    'selector' => null,
])

@php
    use App\Support\ProductVariantSelector;

    $selector ??= ProductVariantSelector::forProduct($product);
    $lowThreshold = (int) config('shop.low_stock_threshold', 5);
@endphp

<div class="product-purchase" x-data="productPurchase(@js([
    'variants' => $selector['variants'],
    'axes' => $selector['axes'],
    'useAxisSelector' => $selector['useAxisSelector'],
    'hasMultipleVariants' => $selector['hasMultipleVariants'],
    'defaultVariantId' => $selector['defaultVariantId'],
    'lowStockThreshold' => $lowThreshold,
    'inWishlist' => $inWishlist,
    'productId' => $product->id,
    'requiresSelection' => $selector['useAxisSelector'] || $selector['hasMultipleVariants'],
    'routes' => [
        'cartStore' => route('shop.cart.store'),
        'wishlistStore' => route('shop.wishlist.store'),
        'wishlistRemove' => url('/wishlist'),
    ],
]))">
    <form method="POST" action="{{ route('shop.cart.store') }}" class="product-purchase__form" @submit="prepareSubmit($event)">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="buy_now" :value="buyNow ? 1 : 0">

        @if ($selector['useAxisSelector'] || $selector['hasMultipleVariants'])
            <input type="hidden" name="product_variant_id" :value="selectedVariantId">
        @else
            <input type="hidden" name="product_variant_id" value="{{ $selector['variants'][0]['id'] ?? '' }}">
        @endif

        @if ($selector['useAxisSelector'])
            <template x-for="axis in axes" :key="axis.slug">
                <div class="product-purchase__section product-purchase__option-group">
                    <div class="product-purchase__label-row">
                        <span class="product-purchase__label" x-text="axis.name"></span>
                        <span class="product-purchase__selected" x-text="selectedOptionLabel(axis.slug)"></span>
                    </div>

                    <div
                        x-show="axis.type === 'color'"
                        class="product-option-swatches"
                        role="listbox"
                        :aria-label="axis.name + ' options'"
                    >
                        <template x-for="option in axis.options" :key="option.value">
                            <button
                                type="button"
                                class="product-color-swatch"
                                role="option"
                                :class="{
                                    'is-active': isOptionSelected(axis.slug, option.value),
                                    'is-unavailable': isOptionUnavailable(axis.slug, option.value),
                                    'is-disabled': isOptionDisabled(axis.slug, option.value),
                                }"
                                :disabled="isOptionDisabled(axis.slug, option.value)"
                                :title="option.value + (isOptionUnavailable(axis.slug, option.value) ? ' — Out of stock' : '')"
                                :aria-label="option.value"
                                :aria-selected="isOptionSelected(axis.slug, option.value)"
                                @click="selectOption(axis.slug, option.value)"
                            >
                                <span
                                    class="product-color-swatch__circle"
                                    :style="`background-color: ${option.hex || '#CCCCCC'}`"
                                ></span>
                                <span class="product-color-swatch__label" x-text="option.value"></span>
                            </button>
                        </template>
                    </div>

                    <div
                        x-show="axis.type !== 'color'"
                        class="product-option-buttons"
                        role="listbox"
                        :aria-label="axis.name + ' options'"
                    >
                        <template x-for="option in axis.options" :key="option.value">
                            <button
                                type="button"
                                class="product-size-btn"
                                role="option"
                                :class="{
                                    'is-active': isOptionSelected(axis.slug, option.value),
                                    'is-disabled': isOptionDisabled(axis.slug, option.value),
                                }"
                                :disabled="isOptionDisabled(axis.slug, option.value)"
                                :title="option.value + (isOptionDisabled(axis.slug, option.value) ? ' — Unavailable' : '')"
                                :aria-label="option.value"
                                :aria-selected="isOptionSelected(axis.slug, option.value)"
                                @click="selectOption(axis.slug, option.value)"
                                x-text="option.value"
                            ></button>
                        </template>
                    </div>
                </div>
            </template>
        @elseif ($selector['hasMultipleVariants'])
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
        @endif

        <div class="product-purchase__price" :class="{ 'is-updating': isUpdatingVariant }">
            <span class="product-purchase__price-current" x-text="selectedVariant?.priceFormatted"></span>
            <span class="product-purchase__price-compare" x-show="selectedVariant?.comparePriceFormatted" x-text="selectedVariant?.comparePriceFormatted"></span>
        </div>

        <div class="product-purchase__meta" :class="{ 'is-updating': isUpdatingVariant }">
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
        <p class="product-purchase__selection-error ds-error-text" x-show="selectionError" x-text="selectionError" x-cloak></p>

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
