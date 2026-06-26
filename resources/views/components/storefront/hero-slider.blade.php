@php
    $slides = [
        [
            'key' => 'new-collection',
            'eyebrow' => 'Just dropped',
            'title' => 'New Collection',
            'subtitle' => 'Discover the latest basins, mixers, and bathroom essentials curated for modern spaces.',
            'badge' => 'New arrivals',
            'badge_class' => 'ds-badge-new !bg-white/15 !text-white border border-white/20',
            'promo' => 'Up to 15% off launch styles',
            'promo_detail' => 'Limited time on select new products',
            'cta_primary' => ['label' => 'Shop new in', 'url' => route('shop.products.index', ['collection' => 'new'])],
            'cta_secondary' => ['label' => 'Explore lookbook', 'url' => route('shop.about')],
            'bg' => 'linear-gradient(135deg, #0b0b0f 0%, #1c1c1e 42%, #003566 100%)',
            'orb_a' => 'bg-accent/40 w-72 h-72 -top-16 -right-10',
            'orb_b' => 'bg-white/10 w-56 h-56 bottom-0 left-1/4',
        ],
        [
            'key' => 'best-sellers',
            'eyebrow' => 'Customer favorites',
            'title' => 'Best Sellers',
            'subtitle' => 'Top-rated fixtures trusted by homeowners, designers, and contractors across Pakistan.',
            'badge' => 'Best sellers',
            'badge_class' => 'ds-badge-neutral !bg-white/15 !text-white border border-white/20',
            'promo' => '4.8★ average rating',
            'promo_detail' => 'Proven quality. Fast delivery.',
            'cta_primary' => ['label' => 'View best sellers', 'url' => route('shop.products.index', ['collection' => 'best-sellers'])],
            'cta_secondary' => ['label' => 'Browse all shop', 'url' => route('shop.products.index')],
            'bg' => 'linear-gradient(135deg, #050507 0%, #111111 45%, #2c2c2e 100%)',
            'orb_a' => 'bg-amber-400/20 w-80 h-80 -top-20 right-1/4',
            'orb_b' => 'bg-white/10 w-64 h-64 -bottom-10 -left-8',
        ],
        [
            'key' => 'flash-sale',
            'eyebrow' => 'Limited time',
            'title' => 'Flash Sale',
            'subtitle' => 'Save big on premium sanitary ware — while stocks last. Ends soon.',
            'badge' => 'Hot deal',
            'badge_class' => 'ds-badge-sale !text-white border border-white/20',
            'promo' => 'Extra 20% off sale items',
            'promo_detail' => 'Auto-applied at checkout on eligible products',
            'cta_primary' => ['label' => 'Shop flash sale', 'url' => route('shop.products.index', ['collection' => 'sale'])],
            'cta_secondary' => ['label' => 'View cart', 'url' => route('shop.cart.index')],
            'bg' => 'linear-gradient(135deg, #3b0a0a 0%, #7f1d1d 38%, #0b0b0f 100%)',
            'orb_a' => 'bg-commerce-sale/35 w-96 h-96 -top-24 -right-16',
            'orb_b' => 'bg-orange-400/15 w-52 h-52 bottom-8 left-8',
        ],
        [
            'key' => 'seasonal-collection',
            'eyebrow' => 'Summer refresh',
            'title' => 'Seasonal Collection',
            'subtitle' => 'Elevate bathrooms and kitchens with seasonal picks — elegant, durable, project-ready.',
            'badge' => 'Seasonal edit',
            'badge_class' => 'ds-badge-accent !bg-white/15 !text-white border border-white/20',
            'promo' => 'Free shipping over '.config('shop.currency_symbol').' 10,000',
            'promo_detail' => 'Perfect for renovations & new builds',
            'cta_primary' => ['label' => 'Shop seasonal', 'url' => route('shop.products.index', ['collection' => 'seasonal'])],
            'cta_secondary' => ['label' => 'Get expert advice', 'url' => route('shop.contact')],
            'bg' => 'linear-gradient(135deg, #0f172a 0%, #134e4a 42%, #0b0b0f 100%)',
            'orb_a' => 'bg-teal-400/25 w-72 h-72 top-10 -left-12',
            'orb_b' => 'bg-sky-400/20 w-64 h-64 -bottom-12 right-10',
        ],
    ];
@endphp

<section
    class="hero-swiper relative overflow-hidden bg-ink"
    data-hero-swiper
    data-autoplay-delay="5500"
    aria-label="Promotional highlights"
>
    <div class="swiper hero-swiper__slider">
        <div class="swiper-wrapper">
            @foreach ($slides as $slide)
                <div class="swiper-slide hero-swiper__slide">
                    {{-- Parallax background --}}
                    <div
                        class="hero-swiper__bg"
                        data-swiper-parallax="-28%"
                        style="background: {{ $slide['bg'] }};"
                    ></div>

                    <div class="hero-swiper__overlay bg-gradient-to-r from-ink/80 via-ink/45 to-transparent"></div>
                    <div class="hero-swiper__grid" data-swiper-parallax="-12%"></div>

                    <div class="hero-swiper__orb {{ $slide['orb_a'] }}" data-swiper-parallax="-18%"></div>
                    <div class="hero-swiper__orb {{ $slide['orb_b'] }}" data-swiper-parallax="-8%"></div>

                    <div class="hero-swiper__shine" data-swiper-parallax="-5%"></div>

                    <div class="hero-swiper__content ds-container">
                        <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-center w-full">
                            {{-- Copy --}}
                            <div class="lg:col-span-7 xl:col-span-6 text-white">
                                <div data-hero-animate>
                                    <span class="{{ $slide['badge_class'] }}">{{ $slide['badge'] }}</span>
                                </div>

                                <p class="mt-4 text-xs sm:text-sm uppercase tracking-[0.24em] text-white/60" data-hero-animate>
                                    {{ $slide['eyebrow'] }}
                                </p>

                                <h2 class="mt-3 text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight leading-[1.05] ds-text-balance" data-hero-animate>
                                    {{ $slide['title'] }}
                                </h2>

                                <p class="mt-4 max-w-xl text-base sm:text-lg text-white/75 leading-relaxed" data-hero-animate>
                                    {{ $slide['subtitle'] }}
                                </p>

                                <div class="mt-8 flex flex-col sm:flex-row gap-3" data-hero-animate>
                                    <a href="{{ $slide['cta_primary']['url'] }}" class="ds-btn-primary ds-btn-lg !bg-white !text-ink hover:!bg-ink-50 justify-center">
                                        {{ $slide['cta_primary']['label'] }}
                                    </a>
                                    <a href="{{ $slide['cta_secondary']['url'] }}" class="ds-btn-secondary ds-btn-lg !border-white/30 !bg-white/5 !text-white hover:!bg-white/10 hover:!border-white/50 justify-center">
                                        {{ $slide['cta_secondary']['label'] }}
                                    </a>
                                </div>
                            </div>

                            {{-- Promotional banner card --}}
                            <div class="lg:col-span-5 xl:col-span-6 hidden sm:block" data-hero-animate>
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
