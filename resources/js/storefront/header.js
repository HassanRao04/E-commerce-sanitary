const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const jsonFetch = async (url, options = {}) => {
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
        throw new Error(`Request failed (${response.status})`);
    }

    return response.json();
};

export default (config = {}) => ({
    cart: config.cart ?? { count: 0, items: [], totals: {} },
    routes: config.routes ?? {},
    overlay: Boolean(config.overlay),

    mobileOpen: false,
    searchOpen: false,
    cartOpen: false,
    accountOpen: false,
    megaOpen: false,
    mobileMegaOpen: false,
    scrolled: !Boolean(config.overlay),
    cartLoading: false,

    init() {
        this.handleScroll = () => {
            if (!this.overlay) {
                this.scrolled = true;
                return;
            }

            // Force solid chrome while overlays/drawers are open for readability.
            if (this.mobileOpen || this.searchOpen || this.cartOpen || this.megaOpen) {
                this.scrolled = true;
                return;
            }

            this.scrolled = window.scrollY > 12;
        };

        this.syncHeaderOffset = () => {
            if (!this.overlay) {
                return;
            }

            const height = Math.ceil(this.$el.getBoundingClientRect().height);
            if (height > 0) {
                document.documentElement.style.setProperty('--storefront-header-offset', `${height}px`);
            }
        };

        window.addEventListener('scroll', this.handleScroll, { passive: true });
        window.addEventListener('resize', this.syncHeaderOffset, { passive: true });
        this.handleScroll();
        this.syncHeaderOffset();

        if (typeof ResizeObserver !== 'undefined') {
            this._headerResizeObserver = new ResizeObserver(() => this.syncHeaderOffset());
            this._headerResizeObserver.observe(this.$el);
        }

        ['mobileOpen', 'searchOpen', 'cartOpen', 'megaOpen'].forEach((key) => {
            this.$watch(key, () => {
                this.syncBodyScroll();
                this.handleScroll();
            });
        });

        document.addEventListener('storefront:cart-added', (event) => {
            if (event.detail) {
                this.cart = event.detail;
            } else {
                this.refreshCart();
            }

            this.openCart();
        });

        document.addEventListener('storefront:cart-updated', (event) => {
            if (event.detail) {
                this.cart = event.detail;
            } else {
                this.refreshCart();
            }
        });
    },

    destroy() {
        window.removeEventListener('scroll', this.handleScroll);
        window.removeEventListener('resize', this.syncHeaderOffset);
        this._headerResizeObserver?.disconnect();
        document.body.classList.remove('overflow-hidden');
    },

    syncBodyScroll() {
        document.body.classList.toggle(
            'overflow-hidden',
            this.mobileOpen || this.searchOpen || this.cartOpen,
        );
    },

    closeAll() {
        this.mobileOpen = false;
        this.searchOpen = false;
        this.cartOpen = false;
        this.accountOpen = false;
        this.megaOpen = false;
        this.mobileMegaOpen = false;
    },

    openSearch() {
        this.mobileOpen = false;
        this.cartOpen = false;
        this.accountOpen = false;
        this.megaOpen = false;
        this.searchOpen = true;
        this.$nextTick(() => this.$refs.searchInput?.focus());
    },

    openCart() {
        this.mobileOpen = false;
        this.searchOpen = false;
        this.accountOpen = false;
        this.megaOpen = false;
        this.cartOpen = true;
        this.refreshCart();
    },

    toggleAccount() {
        this.accountOpen = !this.accountOpen;
    },

    toggleMobile() {
        this.mobileOpen = !this.mobileOpen;
        if (this.mobileOpen) {
            this.searchOpen = false;
            this.cartOpen = false;
            this.accountOpen = false;
            this.megaOpen = false;
        }
    },

    async refreshCart() {
        if (!this.routes.cartPreview) {
            return;
        }

        this.cartLoading = true;

        try {
            this.cart = await jsonFetch(this.routes.cartPreview);
        } catch (error) {
            console.error(error);
        } finally {
            this.cartLoading = false;
        }
    },

    async updateQuantity(itemId, quantity) {
        const qty = Math.max(1, Math.min(99, Number(quantity) || 1));

        if (!this.routes.cartUpdate) {
            return;
        }

        this.cartLoading = true;

        try {
            const body = new FormData();
            body.append('quantity', String(qty));
            body.append('_method', 'PATCH');

            this.cart = await jsonFetch(`${this.routes.cartUpdate}/${itemId}`, {
                method: 'POST',
                body,
            });
        } catch (error) {
            console.error(error);
            await this.refreshCart();
        } finally {
            this.cartLoading = false;
        }
    },

    async removeItem(itemId) {
        if (!this.routes.cartUpdate) {
            return;
        }

        this.cartLoading = true;

        try {
            this.cart = await jsonFetch(`${this.routes.cartUpdate}/${itemId}`, {
                method: 'DELETE',
            });
        } catch (error) {
            console.error(error);
            await this.refreshCart();
        } finally {
            this.cartLoading = false;
        }
    },
});
