{{-- Mega menu panel (desktop) --}}
<div
    x-show="megaOpen"
    x-cloak
    x-transition:enter="transition ease-ds-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    @mouseenter="megaOpen = true"
    @mouseleave="megaOpen = false"
    class="hidden md:block absolute inset-x-0 top-full z-[60] border-b border-ink-100 bg-surface shadow-ds-lg"
    role="region"
    aria-label="Shop categories"
>
    <div class="ds-container py-8">
        <div class="grid grid-cols-12 gap-8">
            {{-- Categories --}}
            <div class="col-span-8">
                <p class="ds-caption mb-4">Browse categories</p>
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-6">
                    @forelse ($headerNavCategories as $category)
                        <div>
                            <a
                                href="{{ route('shop.categories.show', $category) }}"
                                class="ds-heading-4 hover:text-accent transition-colors duration-250 ease-ds-out"
                            >
                                {{ $category->name }}
                            </a>
                            @if ($category->children->isNotEmpty())
                                <ul class="mt-2 space-y-1.5">
                                    @foreach ($category->children as $child)
                                        <li>
                                            <a
                                                href="{{ route('shop.categories.show', $child) }}"
                                                class="ds-body-sm hover:text-ink ds-hover-underline"
                                            >
                                                {{ $child->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <p class="ds-body-sm col-span-full">Categories coming soon.</p>
                    @endforelse
                </div>
            </div>

            {{-- Featured sidebar --}}
            <div class="col-span-4 border-l border-ink-100 pl-8">
                <p class="ds-caption mb-4">Featured</p>
                <div class="space-y-4">
                    <a href="{{ route('shop.products.index') }}" class="group block rounded-ds-lg overflow-hidden border border-ink-100 ds-hover-lift">
                        <div class="bg-ink text-white p-5">
                            <p class="text-xs uppercase tracking-wide text-ink-300">New season</p>
                            <p class="text-lg font-semibold mt-1">Shop all products</p>
                            <p class="text-sm text-ink-300 mt-2">Basins, mixers, toilets &amp; more</p>
                        </div>
                    </a>

                    @if ($headerNavBrands->isNotEmpty())
                        <div>
                            <p class="ds-body-sm font-medium text-ink mb-2">Top brands</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($headerNavBrands as $brand)
                                    <a
                                        href="{{ route('shop.products.index', ['brand' => $brand->id]) }}"
                                        class="ds-badge-neutral hover:bg-ink-200 transition-colors"
                                    >
                                        {{ $brand->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('shop.contact') }}" class="ds-link ds-body-sm font-medium inline-flex items-center gap-1">
                        Need project advice?
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
