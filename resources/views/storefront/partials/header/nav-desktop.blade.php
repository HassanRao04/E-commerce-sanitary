<div class="hidden lg:block ds-container border-t border-ink-100">
    <nav class="flex items-center gap-1 py-2" aria-label="Primary">
        <a
            href="{{ route('shop.home') }}"
            class="rounded-pill px-4 py-2 text-sm font-medium transition-colors duration-250 ease-ds-out {{ request()->routeIs('shop.home') ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink' }}"
        >
            Home
        </a>

        <div
            class="relative"
            @mouseenter="megaOpen = true"
            @mouseleave="megaOpen = false"
        >
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-pill px-4 py-2 text-sm font-medium transition-colors duration-250 ease-ds-out"
                :class="(megaOpen || {{ request()->routeIs('shop.products.*', 'shop.categories.*') ? 'true' : 'false' }}) ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink'"
                @click="megaOpen = !megaOpen"
                aria-haspopup="true"
                :aria-expanded="megaOpen.toString()"
            >
                Shop
                <svg class="h-4 w-4 transition-transform duration-250 ease-ds-out" :class="megaOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>

        <a
            href="{{ route('shop.wishlist.index') }}"
            class="rounded-pill px-4 py-2 text-sm font-medium transition-colors duration-250 ease-ds-out {{ request()->routeIs('shop.wishlist.*') ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink' }}"
        >
            Wishlist
        </a>
        <a
            href="{{ route('shop.orders.track') }}"
            class="rounded-pill px-4 py-2 text-sm font-medium transition-colors duration-250 ease-ds-out {{ request()->routeIs('shop.orders.track*') ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink' }}"
        >
            Track Order
        </a>
        <a
            href="{{ route('shop.about') }}"
            class="rounded-pill px-4 py-2 text-sm font-medium transition-colors duration-250 ease-ds-out {{ request()->routeIs('shop.about') ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink' }}"
        >
            About
        </a>
        <a
            href="{{ route('shop.contact') }}"
            class="rounded-pill px-4 py-2 text-sm font-medium transition-colors duration-250 ease-ds-out {{ request()->routeIs('shop.contact*') ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink' }}"
        >
            Contact
        </a>
    </nav>
</div>
