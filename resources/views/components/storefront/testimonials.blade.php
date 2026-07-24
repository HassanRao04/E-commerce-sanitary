@props([
    'reviews' => collect(),
    'badge' => 'Testimonials',
    'title' => 'Loved by thousands of customers',
    'subtitle' => 'Real stories from homeowners, contractors, and designers who trust us for premium sanitary ware.',
])

@php
    $fallbackTestimonials = [
        [
            'name' => 'Ahmed Khan',
            'location' => 'Karachi',
            'rating' => 5,
            'title' => 'Exactly what we needed',
            'body' => 'Excellent quality basin mixer. Delivery was faster than expected and the packaging kept everything pristine. Will order again for our guest bathroom.',
            'product' => 'Chrome Basin Mixer',
            'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=160&h=160&fit=crop&crop=face',
        ],
        [
            'name' => 'Sara Malik',
            'location' => 'Lahore',
            'rating' => 5,
            'title' => 'Outstanding support team',
            'body' => 'The team helped me pick the right toilet set for our renovation. Professional advice, genuine products, and smooth COD checkout.',
            'product' => 'Wall-Hung Toilet Set',
            'image' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=160&h=160&fit=crop&crop=face',
        ],
        [
            'name' => 'Bilal Raza',
            'location' => 'Islamabad',
            'rating' => 5,
            'title' => 'Premium feel, fair price',
            'body' => 'Genuine branded fixtures at competitive prices. The shower column looks stunning installed — friends keep asking where we bought it.',
            'product' => 'Rain Shower Column',
            'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=160&h=160&fit=crop&crop=face',
        ],
        [
            'name' => 'Fatima Noor',
            'location' => 'Rawalpindi',
            'rating' => 5,
            'title' => 'Contractor approved',
            'body' => 'We specify this store for client projects now. Consistent stock, fast dispatch, and zero issues with warranty claims so far.',
            'product' => 'Designer Faucet Set',
            'image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=160&h=160&fit=crop&crop=face',
        ],
        [
            'name' => 'Usman Ali',
            'location' => 'Faisalabad',
            'rating' => 5,
            'title' => 'Seamless experience',
            'body' => 'Tracked my order from dispatch to delivery. Everything arrived as described — highly recommend for anyone upgrading their bathroom.',
            'product' => 'Vanity Basin Combo',
            'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=160&h=160&fit=crop&crop=face',
        ],
    ];

    $avatarUrl = static function (?string $name, ?string $image = null): string {
        if (filled($image)) {
            return $image;
        }

        $label = urlencode($name ?: 'Customer');

        return "https://ui-avatars.com/api/?name={$label}&background=0b0b0f&color=fff&size=160&bold=true";
    };

    $testimonials = $reviews->isNotEmpty()
        ? $reviews->map(fn ($review) => [
            'name' => $review->user?->name ?? 'Verified buyer',
            'location' => null,
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'body' => $review->body ?? $review->excerpt,
            'product' => $review->product?->name,
            'image' => $avatarUrl($review->user?->name),
        ])
        : collect($fallbackTestimonials);

    $averageRating = round($testimonials->avg('rating') ?: 5, 1);
@endphp

<section
    class="testimonials-section ds-section-tight"
    data-testimonials
    data-autoplay-delay="5500"
    aria-labelledby="testimonials-heading"
>
    <div class="testimonials-section__bg" aria-hidden="true"></div>

    <div class="ds-container relative">
        <header class="testimonials-section__header">
            <div class="testimonials-section__intro anim-gpu" data-aos="fade-up">
                <span class="ds-badge-accent">{{ $badge }}</span>
                <h2 id="testimonials-heading" class="ds-heading-2 mt-3">{{ $title }}</h2>
                <p class="ds-body-sm mt-2 max-w-2xl">{{ $subtitle }}</p>
            </div>

            <div class="testimonials-section__score anim-gpu" data-aos="fade-in" data-aos-delay="150">
                <div class="testimonials-score">
                    <div class="testimonials-score__value">{{ number_format($averageRating, 1) }}</div>
                    <div>
                        <x-storefront.star-rating :rating="$averageRating" size="md" />
                        <p class="testimonials-score__label">{{ $testimonials->count() }}+ verified reviews</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="testimonials-carousel mt-10 anim-gpu" data-aos="fade-right" data-aos-delay="200">
            <div class="testimonials-carousel__nav">
                <button type="button" class="testimonials-carousel__prev ds-btn-icon ds-hover-scale !h-11 !w-11" aria-label="Previous testimonial">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" class="testimonials-carousel__next ds-btn-icon ds-hover-scale !h-11 !w-11" aria-label="Next testimonial">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <div class="testimonials-swiper swiper">
                <div class="swiper-wrapper">
                    @foreach ($testimonials as $testimonial)
                        <div class="swiper-slide">
                            <article class="testimonial-card" data-testimonial-animate>
                                <div class="testimonial-card__glow" aria-hidden="true"></div>

                                <svg class="testimonial-card__quote-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.016 3.016 0 01-3.016 3.016c-1.118 0-2.194-.429-2.992-1.092zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.016 3.016 0 01-3.016 3.016c-1.118 0-2.194-.429-2.992-1.092z"/>
                                </svg>

                                <div class="testimonial-card__rating">
                                    <x-storefront.star-rating :rating="$testimonial['rating']" size="md" />
                                </div>

                                @if (! empty($testimonial['title']))
                                    <h3 class="testimonial-card__title">{{ $testimonial['title'] }}</h3>
                                @endif

                                <blockquote class="testimonial-card__body">
                                    <p>{{ $testimonial['body'] }}</p>
                                </blockquote>

                                <footer class="testimonial-card__footer">
                                    <div class="testimonial-card__avatar-wrap">
                                        <img
                                            src="{{ $testimonial['image'] }}"
                                            alt="{{ $testimonial['name'] }}"
                                            width="56"
                                            height="56"
                                            loading="lazy"
                                            decoding="async"
                                            class="testimonial-card__avatar"
                                        >
                                    </div>
                                    <div class="min-w-0">
                                        <p class="testimonial-card__name">{{ $testimonial['name'] }}</p>
                                        <p class="testimonial-card__meta">
                                            @if (! empty($testimonial['location']))
                                                {{ $testimonial['location'] }}
                                                @if (! empty($testimonial['product']))
                                                    ·
                                                @endif
                                            @endif
                                            @if (! empty($testimonial['product']))
                                                {{ $testimonial['product'] }}
                                            @endif
                                        </p>
                                    </div>
                                </footer>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="testimonials-carousel__footer">
                <div class="testimonials-carousel__pagination"></div>
                <div class="testimonials-carousel__progress" aria-hidden="true">
                    <span class="testimonials-carousel__progress-bar" data-testimonials-progress></span>
                </div>
            </div>
        </div>
    </div>
</section>
