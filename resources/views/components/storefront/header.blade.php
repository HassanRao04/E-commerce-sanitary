@props([
    'cartItemCount' => 0,
    'wishlistItemCount' => 0,
    'headerCartPreview' => ['count' => 0, 'items' => [], 'totals' => []],
    'headerNavCategories' => collect(),
    'headerNavBrands' => collect(),
])

@php
    $headerConfig = [
        'cart' => $headerCartPreview,
        'routes' => [
            'cartPreview' => route('shop.cart.preview'),
            'cartUpdate' => url('/cart/items'),
            'checkout' => route('shop.checkout.index'),
            'cart' => route('shop.cart.index'),
            'wishlist' => route('shop.wishlist.index'),
            'search' => route('shop.products.index'),
        ],
    ];
@endphp

<header
    class="sticky top-0 z-50 relative"
    x-data="storefrontHeader(@js($headerConfig))"
    @keydown.escape.window="closeAll()"
    @click.outside="accountOpen = false; megaOpen = false"
>
    @include('storefront.partials.header.announcement')

    <div
        class="storefront-header-bar transition-shadow duration-300 ease-ds-out"
        :class="scrolled ? 'is-scrolled' : ''"
    >
        @include('storefront.partials.header.main-bar')

        @include('storefront.partials.header.nav-desktop')
    </div>

    @include('storefront.partials.header.mega-menu')
    @include('storefront.partials.header.search-overlay')
    @include('storefront.partials.header.cart-drawer')
    @include('storefront.partials.header.mobile-menu')

    <style>[x-cloak]{display:none!important}</style>
</header>
