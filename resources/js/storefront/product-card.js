const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export default (config = {}) => ({
    wishlisted: config.inWishlist ?? false,
    adding: false,
    togglingWishlist: false,
    productId: config.productId,
    variantId: config.variantId,
    purchasable: config.purchasable ?? true,
    name: config.name ?? '',
    url: config.url ?? '',
    image: config.image ?? '',
    priceFormatted: config.priceFormatted ?? '',
    comparePriceFormatted: config.comparePriceFormatted ?? '',
    sku: config.sku ?? '',
    stockLabel: config.stockLabel ?? '',
    rating: config.rating ?? 0,
    reviewCount: config.reviewCount ?? 0,
    routes: config.routes ?? {},

    async addToCart() {
        if (!this.purchasable || this.adding || !this.routes.cartStore) {
            return;
        }

        this.adding = true;

        try {
            const body = new FormData();
            body.append('product_id', String(this.productId));
            if (this.variantId) {
                body.append('product_variant_id', String(this.variantId));
            }
            body.append('quantity', '1');

            const response = await fetch(this.routes.cartStore, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body,
            });

            if (!response.ok) {
                throw new Error('Add to cart failed');
            }

            const cart = await response.json();
            document.dispatchEvent(new CustomEvent('storefront:cart-added', { detail: cart }));
        } catch (error) {
            console.error(error);
        } finally {
            this.adding = false;
        }
    },

    async toggleWishlist() {
        if (this.togglingWishlist) {
            return;
        }

        this.togglingWishlist = true;

        try {
            const url = this.wishlisted
                ? `${this.routes.wishlistRemove}/${this.productId}`
                : this.routes.wishlistStore;

            const options = this.wishlisted
                ? { method: 'DELETE' }
                : {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: this.productId,
                        product_variant_id: this.variantId,
                    }),
                };

            const response = await fetch(url, {
                ...options,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    ...(options.headers ?? {}),
                },
            });

            if (!response.ok) {
                throw new Error('Wishlist update failed');
            }

            const data = await response.json();
            this.wishlisted = data.in_wishlist;
            document.dispatchEvent(new CustomEvent('storefront:wishlist-updated', { detail: data }));
        } catch (error) {
            console.error(error);
        } finally {
            this.togglingWishlist = false;
        }
    },

    openQuickView() {
        document.dispatchEvent(new CustomEvent('storefront:quick-view', {
            detail: {
                productId: this.productId,
                variantId: this.variantId,
                name: this.name,
                url: this.url,
                image: this.image,
                priceFormatted: this.priceFormatted,
                comparePriceFormatted: this.comparePriceFormatted,
                sku: this.sku,
                stockLabel: this.stockLabel,
                rating: this.rating,
                reviewCount: this.reviewCount,
                purchasable: this.purchasable,
                routes: this.routes,
            },
        }));
    },
});
