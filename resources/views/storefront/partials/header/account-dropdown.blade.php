<div class="relative hidden sm:block">
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-pill border border-ink-200 bg-surface px-3 py-2 text-sm font-medium text-ink transition-all duration-250 ease-ds-out hover:border-ink-300 hover:bg-surface-subtle"
        @click.stop="toggleAccount()"
        :aria-expanded="accountOpen.toString()"
        aria-haspopup="true"
    >
        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-ink text-xs font-semibold text-white">
            @auth
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            @else
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            @endauth
        </span>
        <span class="hidden md:inline max-w-[8rem] truncate">
            @auth
                {{ auth()->user()->name }}
            @else
                Account
            @endif
        </span>
        <svg class="hidden md:block h-4 w-4 text-ink-400 transition-transform duration-250" :class="accountOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
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

{{-- Mobile account icon --}}
<a
    href="{{ auth()->check() ? (auth()->user()->isStaff() ? route('admin.dashboard') : route('shop.account.dashboard')) : route('login') }}"
    class="sm:hidden ds-btn-icon !h-10 !w-10"
    aria-label="Account"
>
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
</a>
