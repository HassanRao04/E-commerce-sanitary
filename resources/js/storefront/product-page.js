/**
 * Product gallery with hover zoom and mobile lightbox.
 */
export function productGallery(images) {
    return {
        images: images.length ? images : [{ url: '', alt: '' }],
        activeIndex: 0,
        zoomEnabled: false,
        zoomStyle: {},
        lightboxOpen: false,

        get activeImage() {
            return this.images[this.activeIndex] ?? this.images[0];
        },

        setImage(index) {
            this.activeIndex = index;
        },

        prevImage() {
            this.activeIndex = (this.activeIndex - 1 + this.images.length) % this.images.length;
        },

        nextImage() {
            this.activeIndex = (this.activeIndex + 1) % this.images.length;
        },

        moveZoom(event) {
            if (!this.zoomEnabled) {
                return;
            }

            const rect = event.currentTarget.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;

            this.zoomStyle = {
                backgroundImage: `url('${this.activeImage.url}')`,
                backgroundPosition: `${x}% ${y}%`,
            };
        },

        openLightbox() {
            this.lightboxOpen = true;
        },
    };
}

/**
 * Variant selection, quantity, wishlist, buy now.
 */
export function productPurchase(config) {
    return {
        variants: config.variants ?? [],
        selectedVariantId: config.defaultVariantId ?? config.variants?.[0]?.id ?? null,
        quantity: 1,
        lowStockThreshold: config.lowStockThreshold ?? 5,
        wishlisted: config.inWishlist ?? false,
        togglingWishlist: false,
        submitting: false,
        buyNow: false,
        routes: config.routes ?? {},

        get selectedVariant() {
            return this.variants.find((variant) => variant.id === this.selectedVariantId) ?? this.variants[0] ?? null;
        },

        get canPurchase() {
            return Boolean(this.selectedVariant?.purchasable);
        },

        selectVariant(id) {
            this.selectedVariantId = id;
            window.dispatchEvent(new CustomEvent('product:variant-changed', {
                detail: { variantId: id },
            }));
        },

        stockLabel() {
            const stock = this.selectedVariant?.stock ?? 0;

            if (stock <= 0) {
                return 'Out of stock';
            }

            if (stock <= this.lowStockThreshold) {
                return `Only ${stock} left`;
            }

            return 'In stock';
        },

        stockBadgeClass() {
            const stock = this.selectedVariant?.stock ?? 0;

            if (stock <= 0) {
                return 'is-danger';
            }

            if (stock <= this.lowStockThreshold) {
                return 'is-warning';
            }

            return 'is-success';
        },

        incrementQty() {
            if (this.quantity < 99) {
                this.quantity += 1;
            }
        },

        decrementQty() {
            if (this.quantity > 1) {
                this.quantity -= 1;
            }
        },

        prepareSubmit() {
            this.submitting = true;
        },

        async toggleWishlist() {
            if (this.togglingWishlist) {
                return;
            }

            this.togglingWishlist = true;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                const url = this.wishlisted
                    ? `${this.routes.wishlistRemove}/${config.productId}`
                    : this.routes.wishlistStore;

                const options = this.wishlisted
                    ? { method: 'DELETE' }
                    : {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            product_id: config.productId,
                            product_variant_id: this.selectedVariantId,
                        }),
                    };

                const response = await fetch(url, {
                    ...options,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                        ...(options.headers ?? {}),
                    },
                });

                if (!response.ok) {
                    throw new Error('Wishlist update failed');
                }

                const data = await response.json();
                this.wishlisted = data.in_wishlist;
                window.dispatchEvent(new CustomEvent('storefront:wishlist-updated', { detail: data }));
            } finally {
                this.togglingWishlist = false;
            }
        },
    };
}
