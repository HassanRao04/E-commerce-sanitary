/**
 * Product gallery with hover zoom, mobile lightbox, and variant-aware image switching.
 */
export function productGallery(config = {}) {
    const fallbackImages = config.fallbackImages?.length
        ? config.fallbackImages
        : [{ url: '', alt: '' }];

    return {
        images: config.initialImages?.length ? [...config.initialImages] : [...fallbackImages],
        fallbackImages,
        imagesByVariant: config.imagesByVariant ?? {},
        currentVariantId: config.defaultVariantId ?? null,
        activeIndex: 0,
        zoomEnabled: false,
        zoomStyle: {},
        lightboxOpen: false,
        isSwitching: false,
        switchTimer: null,

        init() {
            this.onVariantChanged = (event) => {
                this.applyVariant(event.detail?.variantId, event.detail?.variant);
            };

            window.addEventListener('product:variant-changed', this.onVariantChanged);
        },

        destroy() {
            window.removeEventListener('product:variant-changed', this.onVariantChanged);
        },

        get activeImage() {
            return this.images[this.activeIndex] ?? this.images[0] ?? fallbackImages[0];
        },

        resolveVariantImages(variantId, variant = null) {
            if (variant?.images?.length) {
                return variant.images;
            }

            if (variantId === null || variantId === undefined) {
                return this.fallbackImages;
            }

            return this.imagesByVariant[variantId]
                ?? this.imagesByVariant[String(variantId)]
                ?? this.fallbackImages;
        },

        applyVariant(variantId, variant = null) {
            if (variantId === null || variantId === undefined) {
                return;
            }

            const nextImages = this.resolveVariantImages(variantId, variant);

            if (!nextImages?.length) {
                return;
            }

            const sameVariant = String(this.currentVariantId) === String(variantId);
            const sameFirstImage = this.images[0]?.url === nextImages[0]?.url && this.images.length === nextImages.length;

            if (sameVariant && sameFirstImage) {
                return;
            }

            this.currentVariantId = variantId;
            this.activeIndex = 0;
            this.images = [...nextImages];
            this.flashSwitch();
        },

        flashSwitch() {
            this.isSwitching = true;
            clearTimeout(this.switchTimer);
            this.switchTimer = setTimeout(() => {
                this.isSwitching = false;
            }, 220);
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
        axes: config.axes ?? [],
        useAxisSelector: config.useAxisSelector ?? false,
        hasMultipleVariants: config.hasMultipleVariants ?? false,
        selectedVariantId: config.defaultVariantId ?? config.variants?.[0]?.id ?? null,
        selectedOptions: {},
        quantity: 1,
        lowStockThreshold: config.lowStockThreshold ?? 5,
        wishlisted: config.inWishlist ?? false,
        togglingWishlist: false,
        submitting: false,
        buyNow: false,
        selectionError: '',
        requiresSelection: config.requiresSelection ?? false,
        isUpdatingVariant: false,
        updateTimer: null,
        routes: config.routes ?? {},

        init() {
            const defaultVariant = this.variants.find(
                (variant) => variant.id === (config.defaultVariantId ?? this.variants?.[0]?.id),
            ) ?? this.variants[0];

            if (defaultVariant?.options) {
                this.selectedOptions = { ...defaultVariant.options };
            }

            this.syncSelectedVariantId(false);
        },

        get selectedVariant() {
            return this.variants.find((variant) => variant.id === this.selectedVariantId)
                ?? this.findMatchingVariant()
                ?? this.variants[0]
                ?? null;
        },

        get canPurchase() {
            return Boolean(this.selectedVariant?.purchasable);
        },

        findMatchingVariant(options = this.selectedOptions) {
            if (!this.axes.length) {
                return null;
            }

            return this.variants.find((variant) => this.axes.every((axis) => {
                const selected = options[axis.slug];

                if (! selected) {
                    return false;
                }

                return variant.options?.[axis.slug] === selected;
            })) ?? null;
        },

        syncSelectedVariantId(notify = true) {
            const previousId = this.selectedVariantId;
            const match = this.findMatchingVariant();

            if (match) {
                this.selectedVariantId = match.id;
            }

            this.clampQuantityToStock();

            if (notify && this.selectedVariantId !== previousId) {
                this.notifyVariantChanged();
            }
        },

        clampQuantityToStock() {
            const stock = this.selectedVariant?.stock ?? 0;

            if (stock <= 0) {
                return;
            }

            if (this.quantity > stock) {
                this.quantity = stock;
            }
        },

        selectedOptionLabel(slug) {
            return this.selectedOptions[slug] ?? '';
        },

        isOptionSelected(slug, value) {
            return this.selectedOptions[slug] === value;
        },

        variantForOption(slug, value, options = this.selectedOptions) {
            const testOptions = { ...options, [slug]: value };

            return this.findMatchingVariant(testOptions);
        },

        isOptionDisabled(slug, value) {
            const axis = this.axes.find((item) => item.slug === slug);

            if (!axis) {
                return true;
            }

            if (axis.type === 'color') {
                return this.variantForOption(slug, value) === null;
            }

            const variant = this.variantForOption(slug, value);

            return variant === null || !variant.purchasable;
        },

        isOptionUnavailable(slug, value) {
            const variant = this.variantForOption(slug, value);

            return variant !== null && !variant.purchasable;
        },

        selectOption(slug, value) {
            if (this.isOptionDisabled(slug, value)) {
                return;
            }

            this.selectionError = '';
            this.selectedOptions = {
                ...this.selectedOptions,
                [slug]: value,
            };

            this.resolveValidCombination();
            this.syncSelectedVariantId();
        },

        resolveValidCombination() {
            this.axes.forEach((axis) => {
                const current = this.selectedOptions[axis.slug];

                if (!current) {
                    return;
                }

                const variant = this.variantForOption(axis.slug, current);

                if (variant && variant.purchasable) {
                    return;
                }

                const fallback = axis.options.find(
                    (option) => {
                        const candidate = this.variantForOption(axis.slug, option.value);

                        return candidate !== null && candidate.purchasable;
                    },
                );

                if (fallback) {
                    this.selectedOptions[axis.slug] = fallback.value;
                }
            });
        },

        selectVariant(id) {
            const variant = this.variants.find((item) => item.id === id);

            if (!variant) {
                return;
            }

            this.selectionError = '';
            const previousId = this.selectedVariantId;
            this.selectedVariantId = id;

            if (variant.options) {
                this.selectedOptions = { ...variant.options };
            }

            this.clampQuantityToStock();

            if (previousId !== id) {
                this.notifyVariantChanged();
            }
        },

        notifyVariantChanged() {
            this.flashVariantUpdate();

            window.dispatchEvent(new CustomEvent('product:variant-changed', {
                detail: {
                    variantId: this.selectedVariantId,
                    variant: this.selectedVariant,
                },
            }));
        },

        flashVariantUpdate() {
            this.isUpdatingVariant = true;
            clearTimeout(this.updateTimer);
            this.updateTimer = setTimeout(() => {
                this.isUpdatingVariant = false;
            }, 220);
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
            const max = Math.min(99, this.selectedVariant?.stock ?? 99);

            if (this.quantity < max) {
                this.quantity += 1;
            }
        },

        decrementQty() {
            if (this.quantity > 1) {
                this.quantity -= 1;
            }
        },

        hasCompleteSelection() {
            if (this.useAxisSelector) {
                return this.axes.every((axis) => Boolean(this.selectedOptions[axis.slug]));
            }

            if (this.hasMultipleVariants) {
                return Boolean(this.selectedVariantId);
            }

            return Boolean(this.selectedVariantId);
        },

        validateSelection() {
            this.selectionError = '';

            if (this.requiresSelection && ! this.hasCompleteSelection()) {
                this.selectionError = 'Please select all required options before adding to cart.';

                return false;
            }

            if (! this.selectedVariantId) {
                this.selectionError = 'Please select all required options before adding to cart.';

                return false;
            }

            if (! this.canPurchase) {
                this.selectionError = 'The selected variation is not available.';

                return false;
            }

            return true;
        },

        prepareSubmit(event) {
            if (! this.validateSelection()) {
                event.preventDefault();
                this.submitting = false;

                return;
            }

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
