@props([
    'action' => route('shop.products.index'),
    'search' => '',
    'brands' => collect(),
    'categories' => collect(),
    'showCategoryFilter' => true,
])

<aside class="lg:w-64 shrink-0">
    <form method="GET" action="{{ $action }}" class="ds-card ds-card-body space-y-4 sticky top-36">
        <div class="ds-field">
            <label for="filter-search" class="ds-label">Search</label>
            <input id="filter-search" type="search" name="q" value="{{ $search }}" class="ds-input" placeholder="Search products">
        </div>

        @if ($showCategoryFilter)
            <div class="ds-field">
                <label for="filter-category" class="ds-label">Category</label>
                <select id="filter-category" name="category" class="ds-select">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="ds-field">
            <label for="filter-brand" class="ds-label">Brand</label>
            <select id="filter-brand" name="brand" class="ds-select">
                <option value="">All brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((int) request('brand') === $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="ds-field">
            <label class="ds-label">Price range ({{ config('shop.currency_symbol') }})</label>
            <div class="grid grid-cols-2 gap-2">
                <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" placeholder="Min" class="ds-input">
                <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" placeholder="Max" class="ds-input">
            </div>
        </div>

        <div class="ds-field">
            <label for="filter-sort" class="ds-label">Sort by</label>
            <select id="filter-sort" name="sort" class="ds-select">
                <option value="">Newest</option>
                <option value="name" @selected(request('sort') === 'name')>Name (A–Z)</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: Low to High</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: High to Low</option>
            </select>
        </div>

        <button type="submit" class="ds-btn-primary w-full">Apply filters</button>

        @if (request()->hasAny(['q', 'category', 'brand', 'min_price', 'max_price', 'sort']))
            <a href="{{ $action }}" class="block text-center ds-link ds-body-sm">Clear filters</a>
        @endif
    </form>
</aside>
