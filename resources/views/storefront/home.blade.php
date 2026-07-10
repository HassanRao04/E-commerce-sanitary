@extends('layouts.storefront')

@section('title', config('app.name').' — Premium Sanitary Ware Online')
@section('meta_description', 'Shop basins, mixers, toilets, and bathroom accessories from trusted brands. Fast delivery, secure checkout, and expert support.')

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
        <section class="ds-container ds-section-tight">
            <div class="flex items-end justify-between gap-4 mb-6 anim-gpu" data-aos="fade-up">
                <x-storefront.section-header
                    :title="$categoriesConfig['title'] ?? 'Shop by category'"
                    :eyebrow="$categoriesConfig['eyebrow'] ?? 'Browse'"
                    class="mb-0"
                />
                <a href="{{ route('shop.products.index') }}" class="ds-link ds-body-sm font-medium shrink-0">View all</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4" data-gsap-stagger="fade-up" data-gsap-stagger-delay="0.06">
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
                :products="$products"
                :wishlist-product-ids="$wishlistProductIds"
                :view-all-label="$carouselConfig['view_all_label'] ?? 'View all'"
                :view-all-url="route('shop.products.index', ['collection' => $carouselConfig['collection'] ?? $carouselKey])"
            />
        @endif
    @endforeach

    @if (($brandsConfig['enabled'] ?? true) && $brands->isNotEmpty())
        <section class="ds-container ds-section-tight">
            <div class="anim-gpu" data-aos="fade-up">
                <x-storefront.section-header
                    :title="$brandsConfig['title'] ?? 'Trusted brands'"
                    :eyebrow="$brandsConfig['eyebrow'] ?? 'Partners'"
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
        <section class="ds-container pb-16 lg:pb-20">
            <div class="rounded-ds-lg bg-ink text-white p-8 md:p-12 flex flex-col md:flex-row md:items-center md:justify-between gap-6 anim-gpu" data-aos="fade-up" data-aos-duration="700">
                <div>
                    <h2 class="ds-heading-2 text-white">{{ $ctaConfig['title'] ?? 'Need help choosing?' }}</h2>
                    <p class="ds-body-lg !text-ink-300 mt-2">{{ $ctaConfig['subtitle'] ?? 'Our team can recommend the right products for your project.' }}</p>
                </div>
                <a href="{{ $ctaConfig['button_url'] ?? route('shop.contact') }}" class="ds-btn-primary ds-btn-lg !bg-white !text-ink hover:!bg-ink-50 shrink-0" data-gsap-hover="scale">
                    {{ $ctaConfig['button_text'] ?? 'Contact us' }}
                </a>
            </div>
        </section>
    @endif
@endsection
