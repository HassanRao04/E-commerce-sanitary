@props(['category'])

@php
    $productCount = (int) ($category->products_count ?? 0);
    $countLabel = match (true) {
        $productCount === 0 => 'Explore collection',
        $productCount === 1 => '1 product',
        default => number_format($productCount).' products',
    };
@endphp

<a
    href="{{ route('shop.categories.show', $category) }}"
    class="category-card anim-gpu min-w-0 group"
    data-gsap-stagger-item
    aria-label="Shop {{ $category->name }} — {{ $countLabel }}"
>
    <div class="category-card__media">
        <img
            src="{{ $category->display_image_url }}"
            alt="{{ $category->name }}"
            width="640"
            height="800"
            sizes="(max-width: 767px) 100vw, (max-width: 1023px) 50vw, 25vw"
            loading="lazy"
            decoding="async"
            class="category-card__image"
        >

        <div class="category-card__shade" aria-hidden="true"></div>

        <div class="category-card__panel">
            <div class="category-card__copy">
                <span class="category-card__title">{{ $category->name }}</span>
                <span class="category-card__count">{{ $countLabel }}</span>
            </div>

            <span class="category-card__cta" aria-hidden="true">
                <span class="category-card__cta-label">Shop</span>
                <svg class="category-card__cta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </span>
        </div>
    </div>
</a>
