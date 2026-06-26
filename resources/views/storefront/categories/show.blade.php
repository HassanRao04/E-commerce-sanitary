@extends('layouts.storefront')

@section('title', ($pageTitle ?? $category->name).' — '.config('app.name'))
@section('meta_description', $category->description ?: 'Browse '.$category->name.' products at '.config('app.name').'.')

@section('content')
    @php
        $shopConfig = [
            'url' => route('shop.categories.show', $category),
            'priceRange' => $priceRange,
            'total' => $products->total(),
            'filters' => [
                'q' => $search ?? '',
                'categories' => [],
                'brands' => array_values(array_map('intval', array_filter((array) request('brands', request('brand') ? [request('brand')] : [])))),
                'min_price' => request()->filled('min_price') ? (int) request('min_price') : $priceRange['min'],
                'max_price' => request()->filled('max_price') ? (int) request('max_price') : $priceRange['max'],
                'sort' => request('sort', ''),
                'collection' => request('collection', ''),
            ],
            'showCategoryFilter' => false,
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
                ['label' => 'Shop', 'url' => route('shop.products.index')],
                ['label' => $category->name, 'url' => null],
            ]])

            @if ($category->description)
                <p class="shop-category-description">{{ $category->description }}</p>
            @endif

            <div class="shop-layout">
                <x-storefront.shop-filters
                    :brands="$brands"
                    :categories="$categories"
                    :price-range="$priceRange"
                    :show-category-filter="false"
                />

                <div class="shop-layout__main">
                    <x-storefront.shop-toolbar :page-title="$category->name" :total="$products->total()" />

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
                    :show-category-filter="false"
                    :mobile="true"
                />
            </div>
        </div>
    </div>
@endsection
