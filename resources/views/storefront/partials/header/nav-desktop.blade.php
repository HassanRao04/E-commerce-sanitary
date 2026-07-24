@php
    use App\Support\StorefrontHeader;

    $navItems = StorefrontHeader::visibleNavItems();
@endphp

<nav class="storefront-header-nav" aria-label="Primary">
    @foreach ($navItems as $item)
        @php
            $isActive = StorefrontHeader::itemIsActive($item);
            $itemUrl = StorefrontHeader::itemUrl($item);
            $target = ! empty($item['open_in_new_tab']) ? '_blank' : '_self';
            $rel = $target === '_blank' ? 'noopener noreferrer' : null;
        @endphp

        @if (! empty($item['mega_menu']))
            <div
                class="relative"
                @mouseenter="megaOpen = true"
                @mouseleave="megaOpen = false"
            >
                <a
                    href="{{ $itemUrl }}"
                    @class([
                        'storefront-header-nav-link inline-flex items-center gap-1.5 rounded-pill px-3.5 py-2 text-sm font-medium',
                        'is-active' => $isActive,
                    ])
                    :class="{ 'is-open': megaOpen }"
                    aria-haspopup="true"
                    :aria-expanded="megaOpen.toString()"
                    @if ($target === '_blank') target="_blank" rel="{{ $rel }}" @endif
                >
                    {{ $item['label'] }}
                    <svg class="h-3.5 w-3.5 transition-transform duration-250 ease-ds-out" :class="megaOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
            </div>
        @else
            <a
                href="{{ $itemUrl }}"
                @class([
                    'storefront-header-nav-link rounded-pill px-3.5 py-2 text-sm font-medium',
                    'is-active' => $isActive,
                ])
                @if ($target === '_blank') target="_blank" rel="{{ $rel }}" @endif
            >
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
