<div class="ds-container">
    <div class="storefront-header-main">
        {{-- LEFT: mobile menu + logo --}}
        <div class="storefront-header-main__left">
            <button
                type="button"
                class="storefront-header-icon md:hidden ds-btn-icon !h-10 !w-10 shrink-0"
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

            <x-storefront.site-logo class="storefront-header-logo ds-hover-fade" />
        </div>

        {{-- CENTER: primary navigation (desktop / tablet) --}}
        @include('storefront.partials.header.nav-desktop')

        {{-- RIGHT: search · account · cart --}}
        <div class="storefront-header-main__actions">
            <button
                type="button"
                class="storefront-header-icon ds-btn-icon !h-10 !w-10"
                @click="openSearch()"
                aria-label="Open search"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            @include('storefront.partials.header.account-dropdown')

            <button
                type="button"
                class="storefront-header-icon relative ds-btn-icon !h-10 !w-10"
                @click="openCart()"
                aria-label="Open cart"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span
                    x-show="cart.count > 0"
                    x-text="cart.count > 99 ? '99+' : cart.count"
                    class="storefront-header-badge storefront-header-badge--cart"
                ></span>
                @if ($cartItemCount > 0)
                    <span x-cloak x-show="cart.count === 0" class="storefront-header-badge storefront-header-badge--cart">
                        {{ $cartItemCount > 99 ? '99+' : $cartItemCount }}
                    </span>
                @endif
            </button>
        </div>
    </div>
</div>
