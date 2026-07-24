@props([
    'product',
    'selector' => null,
])

@php
    use App\Support\ProductVariantSelector;

    $selector ??= ProductVariantSelector::forProduct($product);
    $gallery = $selector['gallery'];
@endphp

<div
    class="product-gallery"
    x-data="productGallery(@js([
        'initialImages' => $gallery['initialImages'],
        'fallbackImages' => $gallery['fallbackImages'],
        'imagesByVariant' => $gallery['imagesByVariant'],
        'defaultVariantId' => $gallery['defaultVariantId'],
    ]))"
>
    <div class="product-gallery__stage">
        <div
            class="product-gallery__main"
            @mousemove="moveZoom($event)"
            @mouseenter="zoomEnabled = window.matchMedia('(min-width: 1024px)').matches"
            @mouseleave="zoomEnabled = false"
            @click="openLightbox()"
        >
            <img
                :src="activeImage.url"
                :alt="activeImage.alt"
                class="product-gallery__hero"
                :class="{ 'is-switching': isSwitching }"
                width="800"
                height="1000"
                decoding="async"
                fetchpriority="high"
                x-ref="heroImage"
            >
            <div
                class="product-gallery__zoom-lens"
                x-show="zoomEnabled"
                x-cloak
                :style="zoomStyle"
            ></div>
            <button type="button" class="product-gallery__zoom-btn lg:hidden" @click.stop="openLightbox()" aria-label="Zoom image">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
            </button>
        </div>

        <div class="product-gallery__thumbs" role="tablist" aria-label="Product images" x-show="images.length > 1" x-cloak>
            <template x-for="(image, index) in images" :key="`${currentVariantId ?? 'default'}-${index}`">
                <button
                    type="button"
                    class="product-gallery__thumb"
                    :class="{ 'is-active': activeIndex === index }"
                    @click="setImage(index)"
                    :aria-selected="(activeIndex === index).toString()"
                    :aria-label="`Show image ${index + 1}`"
                >
                    <img :src="image.url" :alt="image.alt" width="96" height="96" loading="lazy" decoding="async">
                </button>
            </template>
        </div>
    </div>

    <div
        class="product-gallery-lightbox"
        :class="{ 'is-open': lightboxOpen }"
        x-cloak
        @keydown.escape.window="lightboxOpen = false"
    >
        <div class="product-gallery-lightbox__backdrop" @click="lightboxOpen = false"></div>
        <div class="product-gallery-lightbox__content">
            <button type="button" class="product-gallery-lightbox__close" @click="lightboxOpen = false" aria-label="Close">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <img :src="activeImage.url" :alt="activeImage.alt" class="product-gallery-lightbox__image">
            <div class="product-gallery-lightbox__nav" x-show="images.length > 1">
                <button type="button" class="product-gallery-lightbox__arrow" @click="prevImage()" aria-label="Previous image">‹</button>
                <button type="button" class="product-gallery-lightbox__arrow" @click="nextImage()" aria-label="Next image">›</button>
            </div>
        </div>
    </div>
</div>
