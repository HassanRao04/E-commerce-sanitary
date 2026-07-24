@props([
    'cartItemCount' => 0,
    'wishlistItemCount' => 0,
    'headerCartPreview' => ['count' => 0, 'items' => [], 'totals' => []],
    'headerNavCategories' => collect(),
    'headerNavBrands' => collect(),
])

@php
    $overlayHero = request()->routeIs('shop.home');

    $headerConfig = [
        'cart' => $headerCartPreview,
        'overlay' => $overlayHero,
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
    @class([
        'storefront-header z-50',
        'storefront-header--overlay' => $overlayHero,
        'sticky top-0' => ! $overlayHero,
    ])
    x-data="storefrontHeader(@js($headerConfig))"
    :class="{ 'is-scrolled': scrolled }"
    @keydown.escape.window="closeAll()"
    @click.outside="accountOpen = false; megaOpen = false"
>
    @include('storefront.partials.header.announcement')

    <div class="storefront-header-bar">
        @include('storefront.partials.header.main-bar')
    </div>

    @include('storefront.partials.header.mega-menu')
    @include('storefront.partials.header.search-overlay')
    @include('storefront.partials.header.cart-drawer')
    @include('storefront.partials.header.mobile-menu')

    <style>[x-cloak]{display:none!important}</style>
</header>
