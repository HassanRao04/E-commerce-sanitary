<nav class="lg:w-56 shrink-0">
    <div class="bg-white rounded-xl border border-slate-200 p-3 space-y-1 text-sm font-medium">
        <a href="{{ route('shop.account.dashboard') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('shop.account.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">Dashboard</a>
        <a href="{{ route('shop.account.orders.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('shop.account.orders.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">Orders</a>
        <a href="{{ route('shop.wishlist.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('shop.wishlist.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">Wishlist</a>
        <a href="{{ route('shop.orders.track') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('shop.orders.track*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}">Track Order</a>
        <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">Profile Settings</a>
    </div>
</nav>
