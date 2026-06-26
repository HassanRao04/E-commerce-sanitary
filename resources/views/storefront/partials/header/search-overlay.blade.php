{{-- Full-screen search overlay --}}
<div
    x-show="searchOpen"
    x-cloak
    class="fixed inset-0 z-[60]"
    role="dialog"
    aria-modal="true"
    aria-label="Search products"
>
    <div
        x-show="searchOpen"
        x-transition:enter="transition-opacity ease-ds-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 ds-overlay-dark"
        @click="searchOpen = false"
    ></div>

    <div
        x-show="searchOpen"
        x-transition:enter="transition ease-ds-out duration-350"
        x-transition:enter-start="opacity-0 -translate-y-4 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-4 scale-[0.98]"
        class="relative ds-container pt-8 sm:pt-12"
    >
        <div class="flex items-center justify-between mb-6">
            <p class="ds-caption text-white/80 normal-case tracking-normal">Search our catalog</p>
            <button type="button" class="ds-btn-icon !border-white/20 !bg-white/10 !text-white hover:!bg-white/20" @click="searchOpen = false" aria-label="Close search">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('shop.products.index') }}" method="GET" class="max-w-3xl">
            <div class="relative">
                <label for="overlay-search" class="sr-only">Search products</label>
                <input
                    id="overlay-search"
                    x-ref="searchInput"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search products, brands, SKUs…"
                    class="w-full rounded-ds-lg border-0 bg-white pl-5 pr-24 sm:pr-28 py-4 sm:py-5 text-base sm:text-lg text-ink shadow-ds-lg placeholder:text-ink-400 focus:ring-2 focus:ring-accent/30 focus:outline-none"
                >
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 ds-btn-primary ds-btn-sm shrink-0">
                    Search
                </button>
            </div>
        </form>

        <div class="max-w-3xl mt-8">
            <p class="text-xs uppercase tracking-wide text-white/60 mb-3">Popular</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($headerNavCategories->take(6) as $category)
                    <a
                        href="{{ route('shop.categories.show', $category) }}"
                        class="rounded-pill border border-white/20 bg-white/10 px-4 py-2 text-sm text-white hover:bg-white/20 transition-colors duration-250 ease-ds-out"
                        @click="searchOpen = false"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
                <a
                    href="{{ route('shop.products.index') }}"
                    class="rounded-pill border border-white/20 bg-white/10 px-4 py-2 text-sm text-white hover:bg-white/20 transition-colors duration-250 ease-ds-out"
                    @click="searchOpen = false"
                >
                    All products
                </a>
            </div>
        </div>
    </div>
</div>
