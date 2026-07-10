@props([
    'product',
    'inWishlist' => false,
])

@php
    use App\Services\InventoryControlService;
    use App\Services\ProductPricingService;

    $variant = $product->defaultVariant;
    $pricing = $variant ? app(ProductPricingService::class)->forVariant($variant) : null;
    $inventorySnapshot = $variant ? app(InventoryControlService::class)->snapshot($variant) : null;
    $discount = ($pricing['on_sale'] ?? false) && ($pricing['compare_price'] ?? 0) > 0
        ? (int) round((1 - $pricing['display_price'] / $pricing['compare_price']) * 100)
        : 0;
    $rating = round((float) ($product->average_rating ?? 0), 1);
    $reviewCount = (int) ($product->reviews_count ?? 0);
    $sku = $variant?->sku ?? $product->base_sku;

    $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
    $secondaryImage = $product->images
        ->when($primaryImage, fn ($images) => $images->where('id', '!=', $primaryImage->id))
        ->sortBy('sort_order')
        ->first();

    $primaryImageUrl = $primaryImage?->url ?? $product->primary_image_url;
    $secondaryImageUrl = $secondaryImage?->url;
    $hasSecondaryImage = filled($secondaryImageUrl) && $secondaryImageUrl !== $primaryImageUrl;

    $stockQty = (int) ($inventorySnapshot['available'] ?? 0);
    $lowThreshold = (int) ($inventorySnapshot['low_stock_threshold'] ?? config('shop.low_stock_threshold', 5));
    $stockStatus = match ($inventorySnapshot['status'] ?? 'out_of_stock') {
        'out_of_stock' => ['label' => 'Out of stock', 'badge' => 'ds-badge-danger'],
        'low_stock' => ['label' => 'Low stock', 'badge' => 'ds-badge-warning'],
        default => ['label' => 'In stock', 'badge' => 'ds-badge-success'],
    };

    $cardConfig = [
        'productId' => $product->id,
        'variantId' => $variant?->id,
        'inWishlist' => $inWishlist,
        'purchasable' => (bool) $product->is_purchasable,
        'name' => $product->name,
        'url' => route('shop.products.show', $product),
        'image' => $primaryImageUrl,
        'price' => $pricing ? (string) $pricing['display_price'] : null,
        'priceFormatted' => $pricing ? app(ProductPricingService::class)->format($pricing['display_price']) : null,
        'comparePriceFormatted' => ($pricing['compare_price'] ?? null) !== null
            ? app(ProductPricingService::class)->format($pricing['compare_price'])
            : null,
        'sku' => $sku,
        'stockLabel' => $stockStatus['label'],
        'rating' => $rating,
        'reviewCount' => $reviewCount,
        'routes' => [
            'cartStore' => route('shop.cart.store'),
            'wishlistStore' => route('shop.wishlist.store'),
            'wishlistRemove' => url('/wishlist'),
        ],
    ];
@endphp

<article
    class="product-card product-card--premium ds-product-card group anim-gpu"
    x-data="productCard(@js($cardConfig))"
>
        <div @class(['product-card__media-wrap ds-product-card-media aspect-[4/5]', 'product-card__media-wrap--dual' => $hasSecondaryImage])>
        <a href="{{ route('shop.products.show', $product) }}" class="product-card__overlay-link" aria-label="{{ $product->name }}"></a>

        <div class="product-card__media-stack h-full w-full">
            <img
                src="{{ $primaryImageUrl }}"
                alt="{{ $product->name }}"
                loading="lazy"
                decoding="async"
                class="product-card__image-primary h-full w-full object-cover"
            >
            @if ($hasSecondaryImage)
                <img
                    src="{{ $secondaryImageUrl }}"
                    alt=""
                    loading="lazy"
                    decoding="async"
                    aria-hidden="true"
                    class="product-card__image-secondary h-full w-full object-cover"
                >
            @endif
        </div>

        <div class="product-card__badges">
            @if (($pricing['on_sale'] ?? false) && $discount > 0)
                <span class="ds-badge-sale">−{{ $discount }}%</span>
            @endif
            @if ($product->is_new_arrival)
                <span class="ds-badge-new">New</span>
            @endif
        </div>

        <div class="product-card__actions-top">
            <button
                type="button"
                class="product-card__wishlist ds-btn-icon !h-9 !w-9 !bg-surface/90 backdrop-blur-sm"
                :class="{ 'is-active': wishlisted }"
                @click.stop="toggleWishlist()"
                :disabled="togglingWishlist"
                :aria-pressed="wishlisted.toString()"
                aria-label="Toggle wishlist"
            >
                <svg x-show="!wishlisted" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <svg x-show="wishlisted" x-cloak class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </button>
        </div>

        <div class="product-card__actions-bar">
            <button
                type="button"
                class="product-card__action-btn"
                @click.stop="openQuickView()"
                aria-label="Quick view {{ $product->name }}"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Quick view
            </button>
            <button
                type="button"
                class="product-card__action-btn product-card__action-btn--primary"
                @click.stop="addToCart()"
                :disabled="!purchasable || adding"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span x-show="!adding">{{ $product->is_purchasable ? 'Add to cart' : 'Out of stock' }}</span>
                <span x-show="adding" x-cloak>Adding…</span>
            </button>
        </div>
    </div>

    <div class="product-card__body ds-product-card-body">
        <div class="flex items-center justify-between gap-2 mb-1">
            @if ($product->brand)
                <p class="ds-caption normal-case truncate">{{ $product->brand->name }}</p>
            @else
                <span></span>
            @endif
            <span class="{{ $stockStatus['badge'] }} !text-2xs shrink-0">{{ $stockStatus['label'] }}</span>
        </div>

        <h3 class="ds-heading-4 line-clamp-2">
            <a href="{{ route('shop.products.show', $product) }}" class="hover:underline">{{ $product->name }}</a>
        </h3>

        @if ($sku)
            <p class="product-card__sku mt-1">SKU: {{ $sku }}</p>
        @endif

        <div class="mt-2">
            <x-storefront.star-rating :rating="$rating" :count="$reviewCount" />
        </div>

        @if ($variant && $pricing)
            <div class="product-card__price-row mt-2">
                <span class="text-ink font-semibold">
                    <x-money :amount="$pricing['display_price']" />
                </span>
                @if ($pricing['compare_price'])
                    <span class="product-card__compare-price">
                        <x-money :amount="$pricing['compare_price']" />
                    </span>
                @endif
            </div>
        @endif
    </div>
</article>
