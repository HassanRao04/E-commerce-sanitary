@php
    $slides = $heroSlides ?? app(\App\Services\Storefront\StorefrontContentService::class)->heroSlides();
    $slideLabels = collect($slides)->pluck('title')->values()->all();
@endphp

<section
    class="hero-swiper"
    data-hero-swiper
    data-autoplay-delay="5500"
    data-slide-labels='@json($slideLabels)'
    aria-label="Promotional highlights"
>
    <div class="swiper hero-swiper__slider">
        <div class="swiper-wrapper">
            @foreach ($slides as $slideIndex => $slide)
                <div class="swiper-slide hero-swiper__slide">
                    {{-- Product / lifestyle image (full-bleed; GSAP zoom + fade) --}}
                    <div
                        class="hero-swiper__bg"
                        data-hero-bg
                        data-hero-product-image
                        @if (! empty($slide['image_url']))
                            style="background-image: url('{{ $slide['image_url'] }}');"
                        @else
                            style="background: {{ $slide['bg'] }};"
                        @endif
                    ></div>

                    <div @class([
                        'hero-swiper__overlay',
                        'hero-swiper__overlay--photo' => ! empty($slide['image_url']),
                        'hero-swiper__overlay--fallback' => empty($slide['image_url']),
                    ]) data-hero-overlay></div>

                    <div class="hero-swiper__content ds-container">
                        <div class="hero-swiper__copy" data-hero-copy>
                            @if (filled($slide['badge'] ?? null))
                                <p class="hero-swiper__eyebrow" data-hero-animate="eyebrow">
                                    {{ $slide['badge'] }}
                                </p>
                            @elseif (filled($slide['eyebrow'] ?? null))
                                <p class="hero-swiper__eyebrow" data-hero-animate="eyebrow">
                                    {{ $slide['eyebrow'] }}
                                </p>
                            @endif

                            <h1 class="hero-swiper__title" data-hero-animate="heading">
                                {{ $slide['title'] }}
                            </h1>

                            @if (filled($slide['subtitle'] ?? null))
                                <p class="hero-swiper__subtitle" data-hero-animate="subtitle">
                                    {{ $slide['subtitle'] }}
                                </p>
                            @endif

                            <div class="hero-swiper__actions" data-hero-animate="actions">
                                <a href="{{ $slide['cta_primary']['url'] }}" class="hero-swiper__btn hero-swiper__btn--primary">
                                    {{ $slide['cta_primary']['label'] }}
                                </a>
                                <a href="{{ $slide['cta_secondary']['url'] }}" class="hero-swiper__btn hero-swiper__btn--secondary">
                                    {{ $slide['cta_secondary']['label'] }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div
        class="hero-swiper__progress"
        data-hero-progress
        data-aos="fade"
        data-aos-delay="600"
        data-aos-duration="700"
        aria-hidden="true"
    ></div>

    <button
        type="button"
        class="hero-swiper__nav hero-swiper__nav--prev hero-swiper__prev"
        data-aos="fade"
        data-aos-delay="500"
        data-aos-duration="600"
        aria-label="Previous slide"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button
        type="button"
        class="hero-swiper__nav hero-swiper__nav--next hero-swiper__next"
        data-aos="fade"
        data-aos-delay="500"
        data-aos-duration="600"
        aria-label="Next slide"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>

    <div
        class="hero-swiper__pagination"
        data-aos="fade-up"
        data-aos-delay="450"
        data-aos-duration="650"
    ></div>
</section>
