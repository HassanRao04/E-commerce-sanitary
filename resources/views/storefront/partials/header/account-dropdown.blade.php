<div class="relative hidden md:block">
    <button
        type="button"
        class="storefront-header-account-btn storefront-header-icon inline-flex items-center justify-center rounded-full border !h-10 !w-10 text-sm font-medium transition-all duration-400 ease-ds-out"
        @click.stop="toggleAccount()"
        :aria-expanded="accountOpen.toString()"
        aria-haspopup="true"
        aria-label="Account"
    >
        <span class="storefront-header-account-avatar inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold">
            @auth
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            @else
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            @endauth
        </span>
    </button>

    <div
        x-show="accountOpen"
        x-cloak
        x-transition:enter="transition ease-ds-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
        class="absolute right-0 top-full z-50 mt-2 w-56 origin-top-right rounded-ds-lg border border-ink-100 bg-surface py-2 shadow-ds-lg"
        role="menu"
        @click.stop
    >
        @auth
            <div class="border-b border-ink-100 px-4 py-3">
                <p class="text-sm font-semibold text-ink truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-ink-500 truncate">{{ auth()->user()->email }}</p>
            </div>
            @if (auth()->user()->isStaff())
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Admin dashboard</a>
            @elseif (auth()->user()->isInfluencer())
                <a href="{{ route('influencer.dashboard') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Influencer dashboard</a>
                <a href="{{ route('influencer.orders.index') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Orders</a>
                <a href="{{ route('influencer.wallet') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Wallet</a>
                <a href="{{ route('influencer.commissions.index') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Commissions</a>
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Profile</a>
            @else
                <a href="{{ route('shop.account.dashboard') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Dashboard</a>
                <a href="{{ route('shop.account.orders.index') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">My orders</a>
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Profile</a>
                <a href="{{ route('shop.account.addresses.index') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Addresses</a>
            @endif
            <a href="{{ route('shop.wishlist.index') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Wishlist</a>
            <form method="POST" action="{{ route('logout') }}" class="border-t border-ink-100 mt-1 pt-1">
                @csrf
                <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm text-danger hover:bg-danger-soft" role="menuitem">
                    Sign out
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="block px-4 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" role="menuitem">Login</a>
            <a href="{{ route('register') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Register</a>
            <a href="{{ route('shop.orders.track') }}" class="block px-4 py-2.5 text-sm text-ink-700 hover:bg-surface-muted" role="menuitem">Track order</a>
        @endauth
    </div>
</div>

{{-- Mobile: account lives in the hamburger menu to keep the bar compact --}}
