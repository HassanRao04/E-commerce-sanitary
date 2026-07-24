@extends('layouts.storefront')

@section('title', config('app.name').' — Premium Sanitary Ware Online')
@section('meta_description', 'Shop basins, mixers, toilets, and bathroom accessories from trusted brands. Fast delivery, secure checkout, and expert support.')

@php
    $lcpHeroImage = collect($heroSlides ?? [])->first(fn ($slide) => filled($slide['image_url'] ?? null))['image_url'] ?? null;
@endphp

@push('meta')
    @if ($lcpHeroImage)
        <link rel="preload" as="image" href="{{ $lcpHeroImage }}" fetchpriority="high">
    @endif
@endpush

@section('content')
    <x-storefront.hero-slider />

    @php
        $categoriesConfig = $sections[\App\Support\HomepageSections::CATEGORIES] ?? [];
        $brandsConfig = $sections[\App\Support\HomepageSections::BRANDS] ?? [];
        $testimonialsConfig = $sections[\App\Support\HomepageSections::TESTIMONIALS] ?? [];
        $trustConfig = $sections[\App\Support\HomepageSections::TRUST] ?? [];
        $newsletterConfig = $sections[\App\Support\HomepageSections::NEWSLETTER] ?? [];
        $ctaConfig = $sections[\App\Support\HomepageSections::CTA] ?? [];
    @endphp

    @if (($categoriesConfig['enabled'] ?? true) && $categories->isNotEmpty())
        <section class="ds-container ds-section-tight category-section" aria-label="{{ $categoriesConfig['title'] ?? 'Shop by category' }}">
            <x-storefront.animate effect="fade-up" class="category-section__header">
                <x-storefront.section-header
                    :title="$categoriesConfig['title'] ?? 'Shop by category'"
                    :eyebrow="$categoriesConfig['eyebrow'] ?? 'Browse'"
                    class="mb-0 flex-1 min-w-0"
                />
                <a href="{{ route('shop.products.index') }}" class="home-view-all shrink-0">View all</a>
            </x-storefront.animate>
            <div class="category-grid" data-gsap-stagger="fade-up" data-gsap-stagger-delay="0.08">
                @foreach ($categories as $category)
                    <x-storefront.category-card :category="$category" />
                @endforeach
            </div>
        </section>
    @endif

    @foreach (\App\Support\HomepageSections::carouselKeys() as $carouselKey)
        @php
            $carouselConfig = $sections[$carouselKey] ?? [];
            $products = $carouselProducts[$carouselKey] ?? collect();
        @endphp

        @if (($carouselConfig['enabled'] ?? true) && $products->isNotEmpty())
            <x-storefront.product-carousel
                :id="str($carouselKey)->slug()->toString().'-products'"
                :title="$carouselConfig['title']"
                :subtitle="$carouselConfig['subtitle'] ?? null"
                :badge="$carouselConfig['badge'] ?? null"
                :badge-class="$carouselConfig['badge_class'] ?? 'ds-badge-accent'"
                :theme="$carouselConfig['theme'] ?? 'default'"
                :variant="$carouselKey === \App\Support\HomepageSections::BEST_SELLERS ? 'bestsellers' : null"
                :products="$products"
                :wishlist-product-ids="$wishlistProductIds"
                :view-all-label="$carouselConfig['view_all_label'] ?? 'View all'"
                :view-all-url="route('shop.products.index', ['collection' => $carouselConfig['collection'] ?? $carouselKey])"
            />
        @endif
    @endforeach

    @if (($brandsConfig['enabled'] ?? true) && $brands->isNotEmpty())
        <section class="ds-container ds-section-tight">
            <x-storefront.animate effect="fade-up" class="mb-6">
                <x-storefront.section-header
                    :title="$brandsConfig['title'] ?? 'Trusted brands'"
                    :eyebrow="$brandsConfig['eyebrow'] ?? 'Partners'"
                    class="mb-0"
                />
            </x-storefront.animate>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-3 sm:gap-4" data-gsap-stagger="scale" data-gsap-stagger-delay="0.05">
                @foreach ($brands as $brand)
                    <a href="{{ route('shop.products.index', ['brand' => $brand->id]) }}" class="ds-card-interactive px-3 sm:px-4 py-5 sm:py-6 text-center ds-body-sm font-medium anim-gpu truncate" data-gsap-stagger-item data-gsap-hover="lift" title="{{ $brand->name }}">
                        {{ $brand->name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($testimonialsConfig['enabled'] ?? true)
        <x-storefront.testimonials
            :reviews="$featuredReviews"
            :badge="$testimonialsConfig['badge'] ?? null"
            :title="$testimonialsConfig['title'] ?? null"
            :subtitle="$testimonialsConfig['subtitle'] ?? null"
        />
    @endif

    @if ($trustConfig['enabled'] ?? true)
        <x-storefront.trust-sections :trust="$trustSection ?? $trustConfig" />
    @endif

    @if ($newsletterConfig['enabled'] ?? true)
        <x-storefront.newsletter
            :title="$newsletterConfig['title'] ?? null"
            :subtitle="$newsletterConfig['subtitle'] ?? null"
            :offer="$newsletterConfig['offer'] ?? null"
            :offer-code="$newsletterConfig['offer_code'] ?? null"
            :theme="$newsletterConfig['theme'] ?? 'dark'"
        />
    @endif

    @if ($ctaConfig['enabled'] ?? true)
        <section class="ds-container pb-16 lg:pb-24">
            <x-storefront.animate effect="fade-up" :duration="700" class="home-cta-band bg-ink text-white flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h2 class="ds-heading-2 text-white">{{ $ctaConfig['title'] ?? 'Need help choosing?' }}</h2>
                    <p class="ds-body-lg !text-ink-300 mt-2">{{ $ctaConfig['subtitle'] ?? 'Our team can recommend the right products for your project.' }}</p>
                </div>
                <a href="{{ $ctaConfig['button_url'] ?? route('shop.contact') }}" class="ds-btn-primary ds-btn-lg !bg-white !text-ink hover:!bg-ink-50 shrink-0" data-gsap-hover="lift">
                    {{ $ctaConfig['button_text'] ?? 'Contact us' }}
                </a>
            </x-storefront.animate>
        </section>
    @endif
@endsection
