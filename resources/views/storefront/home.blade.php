@extends('layouts.storefront')

@section('title', config('app.name').' — Premium Sanitary Ware Online')
@section('meta_description', 'Shop basins, mixers, toilets, and bathroom accessories from trusted brands. Fast delivery, secure checkout, and expert support.')

@section('content')
    <x-storefront.hero-slider />

    @if ($categories->isNotEmpty())
        <section class="ds-container ds-section-tight">
            <div class="flex items-end justify-between gap-4 mb-6 anim-gpu" data-aos="fade-up">
                <x-storefront.section-header
                    title="Shop by category"
                    eyebrow="Browse"
                    class="mb-0"
                />
                <a href="{{ route('shop.products.index') }}" class="ds-link ds-body-sm font-medium shrink-0">View all</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4" data-gsap-stagger="fade-up" data-gsap-stagger-delay="0.06">
                @foreach ($categories as $category)
                    <a href="{{ route('shop.categories.show', $category) }}" class="ds-card-interactive p-4 sm:p-5 text-center anim-gpu min-w-0" data-gsap-stagger-item data-gsap-hover="lift">
                        <span class="font-medium text-ink text-sm sm:text-base line-clamp-2">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <x-storefront.product-carousel
        id="featured-products"
        title="Featured products"
        subtitle="Hand-picked premium fixtures for modern bathrooms and kitchens."
        badge="Editor's choice"
        badge-class="ds-badge-accent"
        :products="$featuredProducts"
        :wishlist-product-ids="$wishlistProductIds"
        :view-all-url="route('shop.products.index', ['collection' => 'featured'])"
    />

    <x-storefront.product-carousel
        id="best-selling-products"
        title="Best selling products"
        subtitle="Top-rated picks loved by customers across Pakistan."
        badge="Best sellers"
        badge-class="ds-badge-neutral"
        theme="muted"
        :products="$bestSellingProducts"
        :wishlist-product-ids="$wishlistProductIds"
        :view-all-url="route('shop.products.index', ['collection' => 'best-sellers'])"
    />

    <x-storefront.product-carousel
        id="new-arrivals"
        title="New arrivals"
        subtitle="Fresh styles and latest releases just landed."
        badge="New in"
        badge-class="ds-badge-new"
        :products="$newArrivals"
        :wishlist-product-ids="$wishlistProductIds"
        :view-all-url="route('shop.products.index', ['collection' => 'new'])"
    />

    <x-storefront.product-carousel
        id="trending-products"
        title="Trending products"
        subtitle="What's popular right now — based on recent sales and demand."
        badge="Trending"
        badge-class="ds-badge-neutral !bg-ink !text-white border-0"
        theme="muted"
        :products="$trendingProducts"
        :wishlist-product-ids="$wishlistProductIds"
        :view-all-url="route('shop.products.index', ['collection' => 'trending'])"
    />

    <x-storefront.product-carousel
        id="flash-sale-products"
        title="Flash sale"
        subtitle="Limited-time deals on selected sanitary ware — grab them before they're gone."
        badge="Hot deals"
        badge-class="ds-badge-sale"
        theme="sale"
        :products="$flashSaleProducts"
        :wishlist-product-ids="$wishlistProductIds"
        view-all-label="Shop all deals"
        :view-all-url="route('shop.products.index', ['collection' => 'sale'])"
    />

    @if ($brands->isNotEmpty())
        <section class="ds-container ds-section-tight">
            <div class="anim-gpu" data-aos="fade-up">
                <x-storefront.section-header
                    title="Trusted brands"
                    eyebrow="Partners"
                    class="mb-6"
                />
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-3 sm:gap-4" data-gsap-stagger="scale" data-gsap-stagger-delay="0.05">
                @foreach ($brands as $brand)
                    <a href="{{ route('shop.products.index', ['brand' => $brand->id]) }}" class="ds-card-interactive px-3 sm:px-4 py-5 sm:py-6 text-center ds-body-sm font-medium anim-gpu truncate" data-gsap-stagger-item data-gsap-hover="scale" title="{{ $brand->name }}">
                        {{ $brand->name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <x-storefront.testimonials :reviews="$featuredReviews" />

    <x-storefront.trust-sections />

    <x-storefront.newsletter />

    <section class="ds-container pb-16 lg:pb-20">
        <div class="rounded-ds-lg bg-ink text-white p-8 md:p-12 flex flex-col md:flex-row md:items-center md:justify-between gap-6 anim-gpu" data-aos="fade-up" data-aos-duration="700">
            <div>
                <h2 class="ds-heading-2 text-white">Need help choosing?</h2>
                <p class="ds-body-lg !text-ink-300 mt-2">Our team can recommend the right products for your project.</p>
            </div>
            <a href="{{ route('shop.contact') }}" class="ds-btn-primary ds-btn-lg !bg-white !text-ink hover:!bg-ink-50 shrink-0" data-gsap-hover="scale">
                Contact us
            </a>
        </div>
    </section>
@endsection
