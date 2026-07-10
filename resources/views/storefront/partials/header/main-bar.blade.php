<div class="ds-container">
    <div class="flex items-center justify-between h-16 lg:h-[4.25rem] gap-3">
        {{-- Mobile menu toggle --}}
        <button
            type="button"
            class="lg:hidden ds-btn-icon !h-10 !w-10 shrink-0"
            @click="toggleMobile()"
            :aria-expanded="mobileOpen.toString()"
            aria-controls="mobile-menu-panel"
            aria-label="Open menu"
        >
            <svg x-show="!mobileOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="mobileOpen" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Logo --}}
        <x-storefront.site-logo class="ds-hover-fade" />

        {{-- Desktop quick links --}}
        <div class="hidden lg:flex items-center gap-1 flex-1 justify-center">
            <a href="{{ route('shop.products.index') }}" class="ds-btn-ghost ds-btn-sm">All products</a>
            <a href="{{ route('shop.orders.track') }}" class="ds-btn-ghost ds-btn-sm">Track order</a>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-1 sm:gap-2 shrink-0">
            {{-- Search --}}
            <button
                type="button"
                class="ds-btn-icon !h-10 !w-10"
                @click="openSearch()"
                aria-label="Open search"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            {{-- Wishlist --}}
            <a
                href="{{ route('shop.wishlist.index') }}"
                class="relative ds-btn-icon !h-10 !w-10"
                aria-label="Wishlist"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                @if ($wishlistItemCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-commerce-sale text-white text-2xs font-semibold">
                        {{ $wishlistItemCount > 99 ? '99+' : $wishlistItemCount }}
                    </span>
                @endif
            </a>

            {{-- Account --}}
            @include('storefront.partials.header.account-dropdown')

            {{-- Cart --}}
            <button
                type="button"
                class="relative ds-btn-icon !h-10 !w-10"
                @click="openCart()"
                aria-label="Open cart"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span
                    x-show="cart.count > 0"
                    x-text="cart.count > 99 ? '99+' : cart.count"
                    class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-ink text-white text-2xs font-semibold"
                ></span>
                @if ($cartItemCount > 0)
                    <span x-cloak x-show="cart.count === 0" class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-ink text-white text-2xs font-semibold">
                        {{ $cartItemCount > 99 ? '99+' : $cartItemCount }}
                    </span>
                @endif
            </button>
        </div>
    </div>
</div>
