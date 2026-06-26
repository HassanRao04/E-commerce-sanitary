<nav {{ $attributes->merge(['class' => 'lg:w-60 shrink-0']) }} aria-label="Account navigation">
    <div class="ds-card ds-card-body !p-2 space-y-0.5">
        @php
            $links = [
                ['route' => 'shop.account.dashboard', 'label' => 'Dashboard', 'match' => 'shop.account.dashboard'],
                ['route' => 'shop.account.orders.index', 'label' => 'My Orders', 'match' => 'shop.account.orders.*'],
                ['route' => 'profile.edit', 'label' => 'Profile', 'match' => 'profile.*'],
                ['route' => 'shop.account.addresses.index', 'label' => 'Addresses', 'match' => 'shop.account.addresses.*'],
                ['route' => 'shop.wishlist.index', 'label' => 'Wishlist', 'match' => 'shop.wishlist.*'],
            ];
        @endphp

        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                @class([
                    'block rounded-ds px-3 py-2.5 text-sm font-medium transition-colors',
                    'bg-ink text-white' => request()->routeIs($link['match']),
                    'text-ink-700 hover:bg-surface-muted' => ! request()->routeIs($link['match']),
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach

        <form method="POST" action="{{ route('logout') }}" class="pt-1 border-t border-ink-100 mt-1">
            @csrf
            <button type="submit" class="block w-full rounded-ds px-3 py-2.5 text-left text-sm font-medium text-danger hover:bg-danger-soft transition-colors">
                Logout
            </button>
        </form>
    </div>
</nav>
