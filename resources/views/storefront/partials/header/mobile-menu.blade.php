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
            @include('storefront.partials.header.mobile-menu-nav')
        </div>
    </div>
</div>
