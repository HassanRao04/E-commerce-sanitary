@props([
    'pageTitle' => 'Shop',
    'total' => 0,
])

<div class="shop-toolbar anim-gpu" data-aos="fade-up">
    <div class="shop-toolbar__meta">
        <h1 class="shop-toolbar__title">{{ $pageTitle }}</h1>
        <p class="shop-toolbar__count">
            <span x-text="total.toLocaleString()">{{ number_format($total) }}</span> products found
        </p>
    </div>

    <div class="shop-toolbar__actions">
        <button
            type="button"
            class="shop-toolbar__filters-btn lg:hidden"
            @click="mobileFiltersOpen = true"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            Filters
            <span class="shop-toolbar__filters-badge" x-show="activeFilterCount > 0" x-text="activeFilterCount" x-cloak></span>
        </button>

        <label class="shop-toolbar__sort hidden sm:flex">
            <span class="sr-only">Sort products</span>
            <select class="shop-filters__select shop-toolbar__sort-select" x-model="filters.sort" @change="fetchProducts(1)">
                <option value="">Newest</option>
                <option value="name">Name (A–Z)</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
            </select>
        </label>

        <div class="shop-view-toggle" role="group" aria-label="Product view">
            <button
                type="button"
                class="shop-view-toggle__btn"
                :class="{ 'is-active': viewMode === 'grid' }"
                @click="setViewMode('grid')"
                aria-label="Grid view"
                aria-pressed="viewMode === 'grid'"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            </button>
            <button
                type="button"
                class="shop-view-toggle__btn"
                :class="{ 'is-active': viewMode === 'list' }"
                @click="setViewMode('list')"
                aria-label="List view"
                aria-pressed="viewMode === 'list'"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    <label class="shop-toolbar__sort-row sm:hidden">
        <span class="sr-only">Sort products</span>
        <select class="shop-filters__select" x-model="filters.sort" @change="fetchProducts(1)">
            <option value="">Sort: Newest</option>
            <option value="name">Sort: Name (A–Z)</option>
            <option value="price_asc">Sort: Price low to high</option>
            <option value="price_desc">Sort: Price high to low</option>
        </select>
    </label>
</div>

<div class="shop-active-filters" x-show="activeFilterCount > 0" x-cloak>
    <template x-if="filters.q">
        <button type="button" class="shop-chip" @click="filters.q = ''; fetchProducts(1)">
            Search: <span x-text="filters.q"></span> ×
        </button>
    </template>
    <template x-for="slug in filters.categories" :key="`cat-${slug}`">
        <button type="button" class="shop-chip" @click="toggleCategory(slug)">
            <span x-text="categoryLabel(slug)"></span> ×
        </button>
    </template>
    <template x-for="brandId in filters.brands" :key="`brand-${brandId}`">
        <button type="button" class="shop-chip" @click="toggleBrand(brandId)">
            <span x-text="brandLabel(brandId)"></span> ×
        </button>
    </template>
    <template x-if="priceFilterActive">
        <button type="button" class="shop-chip" @click="resetPrice(); fetchProducts(1)">
            Price: <span x-text="`${formatPrice(filters.min_price)} – ${formatPrice(filters.max_price)}`"></span> ×
        </button>
    </template>
    <button type="button" class="shop-chip shop-chip--clear" @click="clearFilters()">Clear all</button>
</div>
