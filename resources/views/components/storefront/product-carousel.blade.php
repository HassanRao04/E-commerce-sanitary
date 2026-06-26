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
])

@php
    $carouselId = $id ?? str($title)->slug();
    $themeClass = match ($theme) {
        'muted' => 'product-carousel-section--muted',
        'sale' => 'product-carousel-section--sale',
        default => '',
    };
@endphp

@if ($products->isNotEmpty())
    <section
        class="product-carousel-section ds-section-tight {{ $themeClass }}"
        data-product-carousel="{{ $carouselId }}"
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
                        <a href="{{ $viewAllUrl }}" class="ds-link ds-body-sm font-medium hidden sm:inline-flex" data-gsap-hover="lift">
                            {{ $viewAllLabel }}
                        </a>
                    @endif
                    <div class="product-carousel__nav">
                        <button type="button" class="product-carousel__prev ds-btn-icon !h-10 !w-10" aria-label="Previous {{ $title }}" data-gsap-hover="scale">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" class="product-carousel__next ds-btn-icon !h-10 !w-10" aria-label="Next {{ $title }}" data-gsap-hover="scale">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="swiper product-carousel__slider">
                <div class="swiper-wrapper">
                    @foreach ($products as $product)
                        <div class="swiper-slide h-auto">
                            <x-storefront.product-card
                                :product="$product"
                                :in-wishlist="in_array($product->id, $wishlistProductIds, true)"
                            />
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($viewAllUrl)
                <div class="mt-6 sm:hidden">
                    <a href="{{ $viewAllUrl }}" class="ds-btn-secondary w-full text-center">{{ $viewAllLabel }}</a>
                </div>
            @endif
        </div>
    </section>
@endif
