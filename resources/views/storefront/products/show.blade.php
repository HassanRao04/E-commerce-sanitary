@extends('layouts.storefront')

@section('title', $product->name.' — '.config('app.name'))
@section('meta_description', $product->short_description ?: 'Buy '.$product->name.' at '.config('app.name').'.')

@push('meta')
    @if ($primaryImage = ($product->images->firstWhere('is_primary', true) ?? $product->images->first()))
        <meta property="og:image" content="{{ $primaryImage->url ?? $product->primary_image_url }}">
    @endif
@endpush

@section('content')
    <div class="product-page">
        <div class="ds-container ds-section-tight">
            @include('storefront.partials.breadcrumbs', ['items' => array_filter([
                ['label' => 'Shop', 'url' => route('shop.products.index')],
                $product->categories->first() ? ['label' => $product->categories->first()->name, 'url' => route('shop.categories.show', $product->categories->first())] : null,
                ['label' => $product->name, 'url' => null],
            ])])

            <div class="product-page__hero">
                <div class="product-page__gallery">
                    <x-storefront.product-gallery :product="$product" />
                </div>

                <div class="product-page__summary">
                    <div class="product-page__intro">
                        @if ($product->brand)
                            <a href="{{ route('shop.products.index', ['brand' => $product->brand_id]) }}" class="product-page__brand">
                                {{ $product->brand->name }}
                            </a>
                        @endif

                        <h1 class="product-page__title">{{ $product->name }}</h1>

                        @if ($reviewStats['count'] > 0)
                            <div class="product-page__rating">
                                <x-storefront.star-rating :rating="$reviewStats['average']" :count="$reviewStats['count']" size="md" />
                            </div>
                        @endif

                        @if ($product->short_description)
                            <p class="product-page__excerpt">{{ $product->short_description }}</p>
                        @endif

                        <div class="product-page__badges">
                            @if ($product->is_new_arrival)
                                <span class="ds-badge-new">New arrival</span>
                            @endif
                            @if ($product->is_best_seller)
                                <span class="ds-badge-neutral">Best seller</span>
                            @endif
                            @if ($product->is_featured)
                                <span class="ds-badge-accent">Featured</span>
                            @endif
                        </div>
                    </div>

                    <x-storefront.product-purchase :product="$product" :in-wishlist="$inWishlist" />
                </div>
            </div>

            <div class="product-page__tabs">
                <x-storefront.product-tabs
                    :product="$product"
                    :reviews="$reviews"
                    :review-stats="$reviewStats"
                />
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="product-page__related" aria-labelledby="related-products-heading">
                <div class="ds-container">
                    <div class="product-page__related-header">
                        <div>
                            <span class="ds-badge-neutral">You may also like</span>
                            <h2 id="related-products-heading" class="ds-heading-2 mt-2">Related products</h2>
                        </div>
                        <a href="{{ route('shop.products.index') }}" class="ds-link ds-body-sm font-medium hidden sm:inline-flex">View all products</a>
                    </div>

                    <div class="shop-grid shop-grid--grid mt-8">
                        @foreach ($relatedProducts as $related)
                            <x-storefront.product-card
                                :product="$related"
                                :in-wishlist="in_array($related->id, $wishlistProductIds ?? [], true)"
                            />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
