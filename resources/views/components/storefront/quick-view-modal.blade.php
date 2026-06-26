<div
    x-data="storefrontQuickView()"
    @storefront:quick-view.window="show($event.detail)"
    x-cloak
>
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-ds-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="product.name ? `Quick view: ${product.name}` : 'Quick view'"
        @keydown.escape.window="close()"
    >
        <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="close()"></div>

        <div
            x-show="isOpen"
            x-transition:enter="transition ease-ds-out duration-350"
            x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-[0.98]"
            class="relative w-full max-w-3xl max-h-[92vh] overflow-y-auto bg-surface rounded-t-ds-lg sm:rounded-ds-lg shadow-ds-xl"
            @click.stop
        >
            <button
                type="button"
                class="absolute top-3 right-3 z-10 ds-btn-icon !h-9 !w-9 !bg-surface/90"
                @click="close()"
                aria-label="Close quick view"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="grid sm:grid-cols-2 gap-0">
                <div class="aspect-square sm:aspect-auto sm:min-h-[22rem] bg-surface-muted overflow-hidden">
                    <img :src="product.image" :alt="product.name" class="h-full w-full object-cover">
                </div>

                <div class="p-6 sm:p-8 flex flex-col">
                    <p class="ds-caption normal-case" x-text="product.stockLabel"></p>
                    <h2 class="ds-heading-3 mt-2" x-text="product.name"></h2>
                    <p class="product-card__sku mt-2" x-show="product.sku" x-text="`SKU: ${product.sku}`"></p>

                    <div class="mt-3 flex items-center gap-2" x-show="product.rating > 0 || product.reviewCount > 0">
                        <span class="product-card__stars text-sm inline-flex gap-0.5" x-html="renderStars(product.rating)"></span>
                        <span class="text-xs text-ink-500" x-show="product.reviewCount > 0" x-text="`(${product.reviewCount})`"></span>
                    </div>

                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-2xl font-semibold text-ink" x-text="product.priceFormatted"></span>
                        <span class="product-card__compare-price" x-show="product.comparePriceFormatted" x-text="product.comparePriceFormatted"></span>
                    </div>

                    <div class="mt-auto pt-8 flex flex-col sm:flex-row gap-3">
                        <button
                            type="button"
                            class="ds-btn-primary flex-1"
                            @click="addToCart()"
                            :disabled="!product.purchasable || adding"
                        >
                            <span x-show="!adding" x-text="product.purchasable ? 'Add to cart' : 'Out of stock'"></span>
                            <span x-show="adding" x-cloak>Adding…</span>
                        </button>
                        <a :href="product.url" class="ds-btn-secondary flex-1 text-center">View details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
