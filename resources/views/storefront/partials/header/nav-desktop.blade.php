@php
    use App\Support\StorefrontHeader;

    $navItems = StorefrontHeader::visibleNavItems();
@endphp

<div class="hidden lg:block ds-container border-t border-ink-100">
    <nav class="flex items-center gap-1 py-2" aria-label="Primary">
        @foreach ($navItems as $item)
            @php
                $isActive = StorefrontHeader::itemIsActive($item);
                $itemUrl = StorefrontHeader::itemUrl($item);
                $linkClass = 'rounded-pill px-4 py-2 text-sm font-medium transition-colors duration-250 ease-ds-out '
                    . ($isActive ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink');
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
                        class="inline-flex items-center gap-1.5 {{ $linkClass }}"
                        :class="(megaOpen || {{ $isActive ? 'true' : 'false' }}) ? 'bg-ink text-white' : 'text-ink-600 hover:bg-surface-muted hover:text-ink'"
                        aria-haspopup="true"
                        :aria-expanded="megaOpen.toString()"
                        @if ($target === '_blank') target="_blank" rel="{{ $rel }}" @endif
                    >
                        {{ $item['label'] }}
                        <svg class="h-4 w-4 transition-transform duration-250 ease-ds-out" :class="megaOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            @else
                <a
                    href="{{ $itemUrl }}"
                    class="{{ $linkClass }}"
                    @if ($target === '_blank') target="_blank" rel="{{ $rel }}" @endif
                >
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
</div>
