@php
    $slides = $heroSlides ?? app(\App\Services\Storefront\StorefrontContentService::class)->heroSlides();
    $slideLabels = collect($slides)->pluck('title')->values()->all();
@endphp

<section
    class="hero-swiper relative overflow-hidden bg-ink"
    data-hero-swiper
    data-autoplay-delay="5500"
    data-slide-labels='@json($slideLabels)'
    aria-label="Promotional highlights"
>
    <div class="swiper hero-swiper__slider">
        <div class="swiper-wrapper">
            @foreach ($slides as $slideIndex => $slide)
                <div class="swiper-slide hero-swiper__slide">
                    {{-- Parallax background --}}
                    <div
                        class="hero-swiper__bg"
                        data-swiper-parallax="-28%"
                        @if (! empty($slide['image_url']))
                            style="background-image: url('{{ $slide['image_url'] }}'); background-size: cover; background-position: center;"
                        @else
                            style="background: {{ $slide['bg'] }};"
                        @endif
                    ></div>

                    <div @class([
                        'hero-swiper__overlay bg-gradient-to-r from-ink/80 via-ink/45 to-transparent',
                        'hero-swiper__overlay--photo' => ! empty($slide['image_url']),
                    ])></div>
                    <div class="hero-swiper__grid" data-swiper-parallax="-12%"></div>

                    <div class="hero-swiper__orb {{ $slide['orb_a'] }}" data-swiper-parallax="-18%"></div>
                    <div class="hero-swiper__orb {{ $slide['orb_b'] }}" data-swiper-parallax="-8%"></div>

                    <div class="hero-swiper__shine" data-swiper-parallax="-5%"></div>

                    <div class="hero-swiper__content ds-container">
                        <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-center w-full">
                            {{-- Copy --}}
                            <div class="lg:col-span-7 xl:col-span-6 text-white">
                                <div data-hero-animate @class(['is-visible' => $slideIndex === 0])>
                                    <span class="{{ $slide['badge_class'] }}">{{ $slide['badge'] }}</span>
                                </div>

                                <p class="mt-4 text-xs sm:text-sm uppercase tracking-[0.24em] text-white/60" data-hero-animate @class(['is-visible' => $slideIndex === 0])>
                                    {{ $slide['eyebrow'] }}
                                </p>

                                <h2 class="mt-3 text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight leading-[1.05] ds-text-balance" data-hero-animate @class(['is-visible' => $slideIndex === 0])>
                                    {{ $slide['title'] }}
                                </h2>

                                <p class="mt-4 max-w-xl text-base sm:text-lg text-white/75 leading-relaxed" data-hero-animate @class(['is-visible' => $slideIndex === 0])>
                                    {{ $slide['subtitle'] }}
                                </p>

                                <div class="mt-8 flex flex-col sm:flex-row gap-3" data-hero-animate @class(['is-visible' => $slideIndex === 0])>
                                    <a href="{{ $slide['cta_primary']['url'] }}" class="ds-btn-primary ds-btn-lg !bg-white !text-ink hover:!bg-ink-50 justify-center">
                                        {{ $slide['cta_primary']['label'] }}
                                    </a>
                                    <a href="{{ $slide['cta_secondary']['url'] }}" class="ds-btn-secondary ds-btn-lg !border-white/30 !bg-white/5 !text-white hover:!bg-white/10 hover:!border-white/50 justify-center">
                                        {{ $slide['cta_secondary']['label'] }}
                                    </a>
                                </div>
                            </div>

                            {{-- Promotional banner card --}}
                            <div class="lg:col-span-5 xl:col-span-6 hidden sm:block" data-hero-animate @class(['is-visible' => $slideIndex === 0])>
                                <div class="hero-swiper__promo-card p-6 sm:p-8 max-w-md lg:ml-auto" data-swiper-parallax="-120">
                                    <p class="text-xs uppercase tracking-[0.2em] text-white/55">Promotional offer</p>
                                    <p class="mt-3 text-2xl sm:text-3xl font-semibold text-white leading-tight">{{ $slide['promo'] }}</p>
                                    <p class="mt-2 text-sm text-white/65">{{ $slide['promo_detail'] }}</p>

                                    <div class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                                        <div class="flex -space-x-2">
                                            @for ($i = 0; $i < 3; $i++)
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border-2 border-ink/20 bg-white/15 text-xs font-semibold text-white/90">
                                                    {{ ['B', 'M', 'T'][$i] }}
                                                </span>
                                            @endfor
                                        </div>
                                        <p class="text-sm text-white/70">Trusted by thousands of customers nationwide</p>
                                    </div>

                                    <a href="{{ $slide['cta_primary']['url'] }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-white hover:text-white/80 transition-colors">
                                        Claim offer
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Autoplay progress --}}
    <div class="hero-swiper__progress" data-hero-progress aria-hidden="true"></div>

    {{-- Navigation --}}
    <button type="button" class="hero-swiper__nav hero-swiper__nav--prev hero-swiper__prev ds-btn-icon !h-11 !w-11 !border-white/20 !bg-ink/30 !text-white hover:!bg-white/15 backdrop-blur-md hidden sm:inline-flex" aria-label="Previous slide">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button type="button" class="hero-swiper__nav hero-swiper__nav--next hero-swiper__next ds-btn-icon !h-11 !w-11 !border-white/20 !bg-ink/30 !text-white hover:!bg-white/15 backdrop-blur-md hidden sm:inline-flex" aria-label="Next slide">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>

    {{-- Pagination --}}
    <div class="hero-swiper__pagination"></div>
</section>
