@props([
    'brands',
    'categories',
    'priceRange',
    'showCategoryFilter' => true,
    'mobile' => false,
])

@php
    $currency = config('shop.currency_symbol', 'Rs.');
    $selectedBrands = collect((array) request('brands', request('brand') ? [request('brand')] : []))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->all();
    $selectedCategories = collect((array) request('categories', request('category') ? [request('category')] : []))
        ->map(fn ($slug) => (string) $slug)
        ->filter()
        ->all();
    $currentMin = request('min_price', $priceRange['min']);
    $currentMax = request('max_price', $priceRange['max']);
@endphp

<aside @class(['shop-filters', 'shop-filters--mobile' => $mobile]) data-shop-filters>
    <div class="shop-filters__panel">
        @if ($mobile)
            <div class="shop-filters__mobile-header">
                <h2 class="shop-filters__title">Filters</h2>
                <button type="button" class="shop-filters__close ds-btn-icon !h-9 !w-9" @click="mobileFiltersOpen = false" aria-label="Close filters">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="shop-filters__section">
            <label for="shop-search{{ $mobile ? '-mobile' : '' }}" class="shop-filters__label">Search</label>
            <div class="shop-filters__search">
                <svg class="shop-filters__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input
                    id="shop-search{{ $mobile ? '-mobile' : '' }}"
                    type="search"
                    class="shop-filters__input"
                    placeholder="Search products…"
                    x-model.debounce.400ms="filters.q"
                    @input="fetchProducts(1)"
                >
            </div>
        </div>

        @if ($showCategoryFilter && $categories->isNotEmpty())
            <div class="shop-filters__section">
                <p class="shop-filters__label">Categories</p>
                <ul class="shop-filters__checks">
                    @foreach ($categories as $category)
                        <li>
                            <label class="shop-filter-check">
                                <input
                                    type="checkbox"
                                    value="{{ $category->slug }}"
                                    :checked="filters.categories.includes(@js($category->slug))"
                                    @change="toggleCategory(@js($category->slug))"
                                >
                                <span>{{ $category->name }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($brands->isNotEmpty())
            <div class="shop-filters__section">
                <p class="shop-filters__label">Brands</p>
                <ul class="shop-filters__checks shop-filters__checks--scroll">
                    @foreach ($brands as $brand)
                        <li>
                            <label class="shop-filter-check">
                                <input
                                    type="checkbox"
                                    value="{{ $brand->id }}"
                                    :checked="filters.brands.includes({{ $brand->id }})"
                                    @change="toggleBrand({{ $brand->id }})"
                                >
                                <span>{{ $brand->name }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="shop-filters__section">
            <div class="shop-filters__price-header">
                <p class="shop-filters__label">Price range</p>
                <p class="shop-filters__price-values">
                    {{ $currency }}
                    <span x-text="formatPrice(filters.min_price)"></span>
                    –
                    <span x-text="formatPrice(filters.max_price)"></span>
                </p>
            </div>

            <div class="shop-price-slider">
                <div class="shop-price-slider__track">
                    <div class="shop-price-slider__range" :style="priceRangeStyle()"></div>
                </div>
                <input
                    type="range"
                    class="shop-price-slider__input shop-price-slider__input--min"
                    :min="priceBounds.min"
                    :max="priceBounds.max"
                    x-model.number="filters.min_price"
                    @input="onPriceChange()"
                    aria-label="Minimum price"
                >
                <input
                    type="range"
                    class="shop-price-slider__input shop-price-slider__input--max"
                    :min="priceBounds.min"
                    :max="priceBounds.max"
                    x-model.number="filters.max_price"
                    @input="onPriceChange()"
                    aria-label="Maximum price"
                >
            </div>
        </div>

        <div class="shop-filters__section">
            <label for="shop-sort{{ $mobile ? '-mobile' : '' }}" class="shop-filters__label">Sort by</label>
            <select
                id="shop-sort{{ $mobile ? '-mobile' : '' }}"
                class="shop-filters__select"
                x-model="filters.sort"
                @change="fetchProducts(1)"
            >
                <option value="">Newest</option>
                <option value="name">Name (A–Z)</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
            </select>
        </div>

        <div class="shop-filters__actions">
            <button type="button" class="ds-btn-secondary w-full" @click="clearFilters()">Clear all</button>
            @if ($mobile)
                <button type="button" class="ds-btn-primary w-full" @click="mobileFiltersOpen = false">Show results</button>
            @endif
        </div>
    </div>
</aside>
