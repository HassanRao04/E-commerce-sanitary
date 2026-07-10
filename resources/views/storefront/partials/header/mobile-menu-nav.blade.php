@php
    use App\Support\StorefrontHeader;

    $navItems = StorefrontHeader::visibleNavItems();
@endphp

<nav class="space-y-1" aria-label="Mobile">
    @foreach ($navItems as $item)
        @php
            $itemUrl = StorefrontHeader::itemUrl($item);
            $isActive = StorefrontHeader::itemIsActive($item);
            $target = ! empty($item['open_in_new_tab']) ? '_blank' : '_self';
            $rel = $target === '_blank' ? 'noopener noreferrer' : null;
        @endphp

        @if (! empty($item['mega_menu']))
            <div class="rounded-ds">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted {{ $isActive ? 'bg-surface-muted' : '' }}"
                    @click="mobileMegaOpen = !mobileMegaOpen"
                    :aria-expanded="mobileMegaOpen.toString()"
                >
                    {{ $item['label'] }}
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
                    <a href="{{ $itemUrl }}" class="block rounded-ds px-3 py-2 text-sm text-ink-600 hover:bg-surface-muted" @click="mobileOpen = false">All products</a>
                    @foreach ($headerNavCategories as $category)
                        <a href="{{ route('shop.categories.show', $category) }}" class="block rounded-ds px-3 py-2 text-sm text-ink-600 hover:bg-surface-muted" @click="mobileOpen = false">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a
                href="{{ $itemUrl }}"
                class="block rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted {{ $isActive ? 'bg-surface-muted' : '' }}"
                @click="mobileOpen = false"
                @if ($target === '_blank') target="_blank" rel="{{ $rel }}" @endif
            >
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach

    <button type="button" class="block w-full text-left rounded-ds px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface-muted" @click="mobileOpen = false; openCart()">Cart</button>
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
