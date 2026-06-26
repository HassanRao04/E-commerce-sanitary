{{-- Mobile slide-out menu --}}
<div
    x-show="mobileOpen"
    x-cloak
    class="fixed inset-0 z-[55] lg:hidden"
    id="mobile-menu-panel"
    role="dialog"
    aria-modal="true"
    aria-label="Mobile navigation"
>
    <div
        x-show="mobileOpen"
        x-transition:enter="transition-opacity ease-ds-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 ds-overlay-dark"
        @click="mobileOpen = false"
    ></div>

    <div
        x-show="mobileOpen"
        x-transition:enter="transition ease-ds-out duration-350"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="absolute inset-y-0 left-0 flex w-full max-w-sm flex-col bg-surface shadow-ds-lg"
        @click.stop
    >
        <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
            <span class="font-semibold text-ink">{{ config('app.name') }}</span>
            <button type="button" class="ds-btn-icon !h-9 !w-9" @click="mobileOpen = false" aria-label="Close menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-4">
            <nav class="space-y-1" aria-label="Mobile">
                <a href="{{ route('shop.home') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Home</a>

                <div class="rounded-ds">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted"
                        @click="mobileMegaOpen = !mobileMegaOpen"
                        :aria-expanded="mobileMegaOpen.toString()"
                    >
                        Shop
                        <svg class="h-4 w-4 transition-transform duration-250" :class="mobileMegaOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="mobileMegaOpen"
                        x-cloak
                        x-transition:enter="transition ease-ds-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="pl-3 pb-2 space-y-1"
                    >
                        <a href="{{ route('shop.products.index') }}" class="block rounded-ds px-3 py-2 text-sm text-ink-600 hover:bg-surface-muted" @click="mobileOpen = false">All products</a>
                        @foreach ($headerNavCategories as $category)
                            <a href="{{ route('shop.categories.show', $category) }}" class="block rounded-ds px-3 py-2 text-sm text-ink-600 hover:bg-surface-muted" @click="mobileOpen = false">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>

                <a href="{{ route('shop.wishlist.index') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Wishlist</a>
                <button type="button" class="block w-full text-left rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false; openCart()">Cart</button>
                <a href="{{ route('shop.orders.track') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Track order</a>
                <a href="{{ route('shop.about') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">About</a>
                <a href="{{ route('shop.contact') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Contact</a>
            </nav>

            <div class="mt-6 border-t border-ink-100 pt-4 px-3 space-y-2">
                @auth
                    @if (auth()->user()->isStaff())
                        <a href="{{ route('admin.dashboard') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Admin</a>
                    @else
                        <a href="{{ route('shop.account.dashboard') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Dashboard</a>
                        <a href="{{ route('shop.account.orders.index') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">My orders</a>
                        <a href="{{ route('profile.edit') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Profile</a>
                        <a href="{{ route('shop.account.addresses.index') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Addresses</a>
                    @endif
                    <a href="{{ route('shop.wishlist.index') }}" class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false">Wishlist</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="ds-btn-ghost w-full !text-danger">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="ds-btn-primary w-full text-center" @click="mobileOpen = false">Login</a>
                    <a href="{{ route('register') }}" class="ds-btn-secondary w-full text-center" @click="mobileOpen = false">Register</a>
                @endauth
            </div>
        </div>
    </div>
</div>
