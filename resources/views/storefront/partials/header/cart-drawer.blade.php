{{-- Cart drawer --}}
<div
    x-show="cartOpen"
    x-cloak
    class="fixed inset-0 z-[60]"
    role="dialog"
    aria-modal="true"
    aria-label="Shopping cart"
>
    <div
        x-show="cartOpen"
        x-transition:enter="transition-opacity ease-ds-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 ds-overlay-dark"
        @click="cartOpen = false"
    ></div>

    <div
        x-show="cartOpen"
        x-transition:enter="transition ease-ds-out duration-350"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-surface shadow-ds-lg"
        @click.stop
    >
        <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
            <div>
                <h2 class="ds-heading-4">Your cart</h2>
                <p class="ds-body-sm" x-text="cart.count === 1 ? '1 item' : `${cart.count} items`"></p>
            </div>
            <button type="button" class="ds-btn-icon !h-9 !w-9" @click="cartOpen = false" aria-label="Close cart">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4" :class="cartLoading ? 'opacity-60 pointer-events-none' : ''">
            <template x-if="cart.items.length === 0">
                <div class="flex h-full flex-col items-center justify-center text-center py-12">
                    <div class="rounded-full bg-surface-muted p-4 mb-4">
                        <svg class="h-8 w-8 text-ink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="ds-body">Your cart is empty</p>
                    <a href="{{ route('shop.products.index') }}" class="ds-btn-primary ds-btn-sm mt-4" @click="cartOpen = false">Browse products</a>
                </div>
            </template>

            <ul class="space-y-4">
                <template x-for="item in cart.items" :key="item.id">
                    <li class="flex gap-3 border-b border-ink-100 pb-4">
                        <a :href="item.url" class="shrink-0" @click="cartOpen = false">
                            <img :src="item.image" :alt="item.name" class="h-20 w-20 rounded-ds object-cover bg-surface-muted">
                        </a>
                        <div class="flex-1 min-w-0">
                            <a :href="item.url" class="font-medium text-ink line-clamp-2 hover:underline" @click="cartOpen = false" x-text="item.name"></a>
                            <p class="ds-body-sm mt-0.5" x-show="item.variant" x-text="item.variant"></p>
                            <p class="ds-body-sm mt-1" x-text="item.unit_price_formatted + ' each'"></p>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="inline-flex items-center rounded-ds border border-ink-200">
                                    <button type="button" class="px-2.5 py-1 text-ink-500 hover:text-ink" @click="updateQuantity(item.id, item.quantity - 1)" aria-label="Decrease quantity">−</button>
                                    <span class="min-w-[2rem] text-center text-sm font-medium" x-text="item.quantity"></span>
                                    <button type="button" class="px-2.5 py-1 text-ink-500 hover:text-ink" @click="updateQuantity(item.id, item.quantity + 1)" aria-label="Increase quantity">+</button>
                                </div>
                                <button type="button" class="ds-body-sm text-danger hover:underline" @click="removeItem(item.id)">Remove</button>
                            </div>
                        </div>
                        <p class="font-semibold text-ink shrink-0" x-text="item.line_total_formatted"></p>
                    </li>
                </template>
            </ul>
        </div>

        <div class="border-t border-ink-100 bg-surface-subtle/80 px-5 py-5 space-y-4" x-show="cart.items.length > 0">
            <dl class="space-y-2 ds-body-sm">
                <div class="flex justify-between">
                    <dt>Subtotal</dt>
                    <dd x-text="cart.totals.subtotal_formatted"></dd>
                </div>
                <div class="flex justify-between font-semibold text-base pt-2 border-t border-ink-100">
                    <dt>Total</dt>
                    <dd x-text="cart.totals.grand_total_formatted"></dd>
                </div>
            </dl>
            <a href="{{ route('shop.checkout.index') }}" class="ds-btn-primary ds-btn-lg w-full" @click="cartOpen = false">
                Checkout
            </a>
            <a href="{{ route('shop.cart.index') }}" class="ds-btn-secondary w-full text-center" @click="cartOpen = false">
                View full cart
            </a>
        </div>
    </div>
</div>
