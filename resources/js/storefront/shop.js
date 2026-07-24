/**
 * Shop catalog — AJAX filtering, sorting, grid/list toggle.
 */
export default function shopCatalog(config) {
    const priceBounds = {
        min: Number(config.priceRange?.min ?? 0),
        max: Number(config.priceRange?.max ?? 100000),
    };

    return {
        baseUrl: config.url,
        priceBounds,
        filters: {
            q: config.filters?.q ?? '',
            categories: [...(config.filters?.categories ?? [])],
            brands: [...(config.filters?.brands ?? [])],
            min_price: Number(config.filters?.min_price ?? priceBounds.min),
            max_price: Number(config.filters?.max_price ?? priceBounds.max),
            sort: config.filters?.sort ?? '',
            collection: config.filters?.collection ?? '',
        },
        categoryLabels: config.categoryLabels ?? {},
        brandLabels: config.brandLabels ?? {},
        total: Number(config.total ?? 0),
        loading: false,
        mobileFiltersOpen: false,
        viewMode: localStorage.getItem('shop-view-mode') ?? 'grid',
        priceDebounce: null,

        init() {
            this.syncViewMode();
            this.bindResultsRoot();
        },

        get activeFilterCount() {
            let count = 0;

            if (this.filters.q) {
                count += 1;
            }

            count += this.filters.categories.length;
            count += this.filters.brands.length;

            if (this.priceFilterActive) {
                count += 1;
            }

            if (this.filters.sort) {
                count += 1;
            }

            return count;
        },

        get priceFilterActive() {
            return this.filters.min_price > this.priceBounds.min
                || this.filters.max_price < this.priceBounds.max;
        },

        formatPrice(value) {
            return Number(value ?? 0).toLocaleString();
        },

        categoryLabel(slug) {
            return this.categoryLabels[slug] ?? slug;
        },

        brandLabel(id) {
            return this.brandLabels[id] ?? `Brand #${id}`;
        },

        priceRangeStyle() {
            const min = this.priceBounds.min;
            const max = this.priceBounds.max;
            const range = Math.max(max - min, 1);
            const left = ((this.filters.min_price - min) / range) * 100;
            const right = ((max - this.filters.max_price) / range) * 100;

            return {
                left: `${Math.max(0, left)}%`,
                right: `${Math.max(0, right)}%`,
            };
        },

        onPriceChange() {
            if (this.filters.min_price > this.filters.max_price) {
                this.filters.min_price = this.filters.max_price;
            }

            if (this.filters.max_price < this.filters.min_price) {
                this.filters.max_price = this.filters.min_price;
            }x

            window.clearTimeout(this.priceDebounce);
            this.priceDebounce = window.setTimeout(() => this.fetchProducts(1), 350);
        },

        toggleCategory(slug) {
            const index = this.filters.categories.indexOf(slug);

            if (index >= 0) {
                this.filters.categories.splice(index, 1);
            } else {
                this.filters.categories.push(slug);
            }

            this.fetchProducts(1);
        },

        toggleBrand(id) {
            const brandId = Number(id);
            const index = this.filters.brands.indexOf(brandId);

            if (index >= 0) {
                this.filters.brands.splice(index, 1);
            } else {
                this.filters.brands.push(brandId);
            }

            this.fetchProducts(1);
        },

        resetPrice() {
            this.filters.min_price = this.priceBounds.min;
            this.filters.max_price = this.priceBounds.max;
        },

        clearFilters() {
            this.filters.q = '';
            this.filters.categories = [];
            this.filters.brands = [];
            this.filters.sort = '';
            this.resetPrice();
            this.fetchProducts(1);
        },

        setViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('shop-view-mode', mode);
            this.syncViewMode();
        },

        syncViewMode() {
            this.$root.querySelectorAll('[data-shop-grid]').forEach((grid) => {
                grid.classList.toggle('shop-grid--list', this.viewMode === 'list');
                grid.classList.toggle('shop-grid--grid', this.viewMode !== 'list');
            });
        },

        buildParams(page = 1) {
            const params = new URLSearchParams();

            if (this.filters.q) {
                params.set('q', this.filters.q);
            }

            this.filters.categories.forEach((slug) => params.append('categories[]', slug));
            this.filters.brands.forEach((id) => params.append('brands[]', String(id)));

            if (this.priceFilterActive) {
                params.set('min_price', String(this.filters.min_price));
                params.set('max_price', String(this.filters.max_price));
            }

            if (this.filters.sort) {
                params.set('sort', this.filters.sort);
            }

            if (this.filters.collection) {
                params.set('collection', this.filters.collection);
            }

            if (page > 1) {
                params.set('page', String(page));
            }

            return params;
        },

        async fetchProducts(page = 1) {
            this.loading = true;

            const params = this.buildParams(page);
            const requestUrl = `${this.baseUrl}?${params.toString()}`;

            try {
                const response = await fetch(requestUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to load products');
                }

                const data = await response.json();
                const root = this.$root.querySelector('[data-shop-results-root]');

                if (root) {
                    root.innerHTML = data.html;
                    this.total = Number(data.total ?? 0);

                    if (window.Alpine) {
                        window.Alpine.initTree(root);
                    }

                    this.bindResultsRoot();
                    this.syncViewMode();
                }

                window.history.replaceState({}, '', requestUrl);
            } catch (error) {
                window.location.href = requestUrl;
            } finally {
                this.loading = false;
            }
        },

        bindResultsRoot() {
            const root = this.$root.querySelector('[data-shop-results-root]');

            if (!root) {
                return;
            }

            root.querySelectorAll('.shop-pagination a').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();

                    const url = new URL(link.href, window.location.origin);
                    const page = Number(url.searchParams.get('page') ?? 1);

                    this.fetchProducts(page);
                    root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            root.querySelector('[data-shop-clear-filters]')?.addEventListener('click', () => {
                this.clearFilters();
            });
        },
    };
}
