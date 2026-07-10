export default function homepageProductPicker(config = {}) {
    return {
        sectionKey: config.sectionKey ?? '',
        searchUrl: config.searchUrl ?? '',
        query: '',
        results: [],
        selected: Array.isArray(config.initialProducts) ? [...config.initialProducts] : [],

        async search() {
            if (this.query.trim().length < 2) {
                this.results = [];

                return;
            }

            try {
                const response = await fetch(
                    `${this.searchUrl}?q=${encodeURIComponent(this.query.trim())}`,
                    { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } },
                );

                if (! response.ok) {
                    this.results = [];

                    return;
                }

                const products = await response.json();
                const selectedIds = this.selected.map((product) => product.id);

                this.results = products.filter((product) => ! selectedIds.includes(product.id));
            } catch {
                this.results = [];
            }
        },

        add(product) {
            if (this.selected.some((item) => item.id === product.id)) {
                return;
            }

            this.selected.push(product);
            this.results = this.results.filter((item) => item.id !== product.id);
            this.query = '';
        },

        remove(productId) {
            this.selected = this.selected.filter((product) => product.id !== productId);
        },
    };
}
