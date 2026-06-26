<div class="shop-results" data-shop-results>
    <div @class(['shop-grid', 'shop-grid--list' => ($view ?? 'grid') === 'list']) data-shop-grid>
        @forelse ($products as $product)
            <x-storefront.product-card
                :product="$product"
                :in-wishlist="in_array($product->id, $wishlistProductIds ?? [], true)"
            />
        @empty
            <div class="shop-grid__empty">
                <div class="shop-empty-state">
                    <svg class="shop-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <h3 class="shop-empty-state__title">No products found</h3>
                    <p class="shop-empty-state__text">Try adjusting your filters or search terms.</p>
                    <button type="button" class="ds-btn-primary ds-btn-sm mt-4" data-shop-clear-filters>Clear filters</button>
                </div>
            </div>
        @endforelse
    </div>

    @if ($products->hasPages())
        <nav class="shop-pagination" aria-label="Product pagination">
            {{ $products->onEachSide(1)->links() }}
        </nav>
    @endif
</div>
