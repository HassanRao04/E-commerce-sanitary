const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export default () => ({
    isOpen: false,
    adding: false,
    product: {
        productId: null,
        variantId: null,
        name: '',
        url: '',
        image: '',
        priceFormatted: '',
        comparePriceFormatted: '',
        sku: '',
        stockLabel: '',
        rating: 0,
        reviewCount: 0,
        purchasable: true,
        routes: {},
    },

    show(detail) {
        this.product = {
            productId: detail.productId,
            variantId: detail.variantId,
            name: detail.name ?? '',
            url: detail.url ?? '#',
            image: detail.image ?? '',
            priceFormatted: detail.priceFormatted ?? '',
            comparePriceFormatted: detail.comparePriceFormatted ?? '',
            sku: detail.sku ?? '',
            stockLabel: detail.stockLabel ?? '',
            rating: Number(detail.rating ?? 0),
            reviewCount: Number(detail.reviewCount ?? 0),
            purchasable: detail.purchasable ?? true,
            routes: detail.routes ?? {},
        };
        this.isOpen = true;
        document.body.classList.add('overflow-hidden');
    },

    close() {
        this.isOpen = false;
        document.body.classList.remove('overflow-hidden');
    },

    renderStars(rating) {
        const value = Math.max(0, Math.min(5, Number(rating) || 0));
        const full = Math.floor(value);
        const half = value - full >= 0.5;
        let html = '';

        for (let i = 1; i <= 5; i += 1) {
            if (i <= full) {
                html += '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
            } else if (half && i === full + 1) {
                html += '<svg class="h-4 w-4 text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" fill-opacity="0.45"/></svg>';
            } else {
                html += '<svg class="h-4 w-4 text-ink-200" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
            }
        }

        return html;
    },

    async addToCart() {
        if (!this.product.purchasable || this.adding || !this.product.routes?.cartStore) {
            return;
        }

        this.adding = true;

        try {
            const body = new FormData();
            body.append('product_id', String(this.product.productId));
            if (this.product.variantId) {
                body.append('product_variant_id', String(this.product.variantId));
            }
            body.append('quantity', '1');

            const response = await fetch(this.product.routes.cartStore, {
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
            this.close();
        } catch (error) {
            console.error(error);
        } finally {
            this.adding = false;
        }
    },
});
