@props(['category'])

<a
    href="{{ route('shop.categories.show', $category) }}"
    class="category-card ds-card-interactive anim-gpu min-w-0 group"
    data-gsap-stagger-item
    data-gsap-hover="lift"
    aria-label="Browse {{ $category->name }}"
>
    <div class="category-card__media">
        <img
            src="{{ $category->display_image_url }}"
            alt="{{ $category->name }}"
            loading="lazy"
            class="category-card__image"
        >
    </div>
    <div class="category-card__body">
        <span class="category-card__title">{{ $category->name }}</span>
    </div>
</a>
