@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeClass' => 'ds-badge-accent',
    'viewAllUrl' => null,
    'viewAllLabel' => 'View all',
    'products',
    'wishlistProductIds' => [],
    'theme' => 'default',
    'id' => null,
    'variant' => null,
])

@php
    $carouselId = $id ?? str($title)->slug();
    $isBestsellers = $variant === 'bestsellers';
    $themeClass = match ($theme) {
        'muted' => 'product-carousel-section--muted',
        'sale' => 'product-carousel-section--sale',
        default => '',
    };
    $variantClass = $isBestsellers ? 'product-carousel-section--bestsellers' : '';
@endphp

@if ($products->isNotEmpty())
    <section
        class="product-carousel-section ds-section-tight {{ $themeClass }} {{ $variantClass }}"
        data-product-carousel="{{ $carouselId }}"
        @if ($isBestsellers) data-carousel-variant="bestsellers" @endif
        aria-label="{{ $title }}"
    >
        <div class="ds-container">
            <div class="product-carousel__header anim-gpu" data-aos="fade-up">
                <x-storefront.section-header
                    :title="$title"
                    :subtitle="$subtitle"
                    :badge="$badge"
                    :badge-class="$badgeClass"
                    class="flex-1 min-w-0"
                />

                <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto sm:justify-end">
                    @if ($viewAllUrl)
                        <a href="{{ $viewAllUrl }}" class="home-view-all hidden sm:inline-flex">
                            {{ $viewAllLabel }}
                        </a>
                    @endif
                    <div class="product-carousel__nav">
                        <button type="button" class="product-carousel__prev ds-btn-icon ds-hover-scale !h-10 !w-10" aria-label="Previous {{ $title }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" class="product-carousel__next ds-btn-icon ds-hover-scale !h-10 !w-10" aria-label="Next {{ $title }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="product-carousel__track anim-gpu" data-aos="fade-up" data-aos-delay="120">
                <div class="swiper product-carousel__slider">
                    <div class="swiper-wrapper">
                        @foreach ($products as $product)
                            <div class="swiper-slide h-auto" @if ($isBestsellers) data-rank="{{ $loop->iteration }}" @endif>
                                <x-storefront.product-card
                                    :product="$product"
                                    :in-wishlist="in_array($product->id, $wishlistProductIds, true)"
                                    :rank="$isBestsellers ? $loop->iteration : null"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($isBestsellers)
                    <div class="product-carousel__progress" aria-hidden="true">
                        <span class="product-carousel__progress-bar"></span>
                    </div>
                @endif
            </div>

            @if ($viewAllUrl)
                <div class="mt-6 sm:hidden">
                    <a href="{{ $viewAllUrl }}" class="ds-btn-secondary w-full text-center">{{ $viewAllLabel }}</a>
                </div>
            @endif
        </div>
    </section>
@endif
