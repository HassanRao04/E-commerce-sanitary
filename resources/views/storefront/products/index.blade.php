@extends('layouts.storefront')

@section('title', ($pageTitle ?? 'Shop').' — '.config('app.name'))
@section('meta_description', 'Browse our full range of sanitary ware. Filter by category, brand, and price.')

@section('content')
    @php
        $shopConfig = [
            'url' => route('shop.products.index'),
            'priceRange' => $priceRange,
            'total' => $products->total(),
            'filters' => [
                'q' => $search ?? '',
                'categories' => array_values(array_filter((array) request('categories', request('category') ? [request('category')] : []))),
                'brands' => array_values(array_map('intval', array_filter((array) request('brands', request('brand') ? [request('brand')] : [])))),
                'min_price' => request()->filled('min_price') ? (int) request('min_price') : $priceRange['min'],
                'max_price' => request()->filled('max_price') ? (int) request('max_price') : $priceRange['max'],
                'sort' => request('sort', ''),
                'collection' => request('collection', ''),
            ],
            'showCategoryFilter' => $showCategoryFilter ?? true,
            'categoryLabels' => $categories->pluck('name', 'slug'),
            'brandLabels' => $brands->pluck('name', 'id'),
        ];
    @endphp

    <div
        class="shop-page"
        x-data="shopCatalog(@js($shopConfig))"
        data-shop-catalog
    >
        <div class="ds-container ds-section-tight">
            @include('storefront.partials.breadcrumbs', ['items' => [
                ['label' => 'Shop', 'url' => null],
            ]])

            <div class="shop-layout">
                <x-storefront.shop-filters
                    :brands="$brands"
                    :categories="$categories"
                    :price-range="$priceRange"
                    :show-category-filter="$showCategoryFilter ?? true"
                />

                <div class="shop-layout__main">
                    <x-storefront.shop-toolbar :page-title="$pageTitle ?? 'Shop'" :total="$products->total()" />

                    <div class="shop-results-wrap" :class="{ 'is-loading': loading }">
                        <div class="shop-results-loader" x-show="loading" x-cloak aria-hidden="true">
                            <span class="shop-results-loader__bar"></span>
                        </div>

                        <div data-shop-results-root>
                            @include('storefront.products.partials.results', [
                                'products' => $products,
                                'wishlistProductIds' => $wishlistProductIds,
                                'view' => 'grid',
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="shop-filters-drawer"
            :class="{ 'is-open': mobileFiltersOpen }"
            x-cloak
            @keydown.escape.window="mobileFiltersOpen = false"
        >
            <div class="shop-filters-drawer__backdrop" @click="mobileFiltersOpen = false"></div>
            <div class="shop-filters-drawer__panel">
                <x-storefront.shop-filters
                    :brands="$brands"
                    :categories="$categories"
                    :price-range="$priceRange"
                    :show-category-filter="$showCategoryFilter ?? true"
                    :mobile="true"
                />
            </div>
        </div>
    </div>
@endsection
