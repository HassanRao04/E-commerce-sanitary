@props([
    'product',
    'reviews',
    'reviewStats',
])

@php
    $checkoutRules = $checkoutRules ?? [
        'free_shipping_enabled' => false,
        'free_shipping_threshold' => 0,
    ];
    $freeShippingThreshold = (float) ($checkoutRules['free_shipping_threshold'] ?? 0);
    $currencySymbol = config('shop.currency_symbol', 'Rs.');
@endphp

<div class="product-tabs" x-data="{ activeTab: 'description' }">
    <div class="product-tabs__nav" role="tablist" aria-label="Product information">
        <button type="button" role="tab" class="product-tabs__tab" :class="{ 'is-active': activeTab === 'description' }" @click="activeTab = 'description'" :aria-selected="(activeTab === 'description').toString()">Description</button>
        <button type="button" role="tab" class="product-tabs__tab" :class="{ 'is-active': activeTab === 'specs' }" @click="activeTab = 'specs'" :aria-selected="(activeTab === 'specs').toString()">Specifications</button>
        <button type="button" role="tab" class="product-tabs__tab" :class="{ 'is-active': activeTab === 'reviews' }" @click="activeTab = 'reviews'" :aria-selected="(activeTab === 'reviews').toString()">
            Reviews
            @if ($reviewStats['count'] > 0)
                <span class="product-tabs__count">({{ $reviewStats['count'] }})</span>
            @endif
        </button>
        <button type="button" role="tab" class="product-tabs__tab" :class="{ 'is-active': activeTab === 'shipping' }" @click="activeTab = 'shipping'" :aria-selected="(activeTab === 'shipping').toString()">Shipping</button>
    </div>

    <div class="product-tabs__panels">
        <section x-show="activeTab === 'description'" x-cloak role="tabpanel" class="product-tabs__panel">
            @if ($product->description)
                <div class="product-tabs__prose">
                    {!! nl2br(e($product->description)) !!}
                </div>
            @elseif ($product->short_description)
                <p class="product-tabs__prose">{{ $product->short_description }}</p>
            @else
                <p class="product-tabs__empty">Detailed description coming soon.</p>
            @endif
        </section>

        <section x-show="activeTab === 'specs'" x-cloak role="tabpanel" class="product-tabs__panel">
            <dl class="product-specs">
                @if ($product->material)
                    <div class="product-specs__row">
                        <dt>Material</dt>
                        <dd>{{ $product->material }}</dd>
                    </div>
                @endif
                @if ($product->installation_type)
                    <div class="product-specs__row">
                        <dt>Installation</dt>
                        <dd>{{ $product->installation_type }}</dd>
                    </div>
                @endif
                @if ($product->base_sku)
                    <div class="product-specs__row">
                        <dt>Base SKU</dt>
                        <dd>{{ $product->base_sku }}</dd>
                    </div>
                @endif
                @if ($product->warranty_text)
                    <div class="product-specs__row">
                        <dt>Warranty</dt>
                        <dd>{{ $product->warranty_text }}</dd>
                    </div>
                @endif
                @foreach ($product->attributeValues as $attributeValue)
                    <div class="product-specs__row">
                        <dt>{{ $attributeValue->attribute?->name }}</dt>
                        <dd>{{ $attributeValue->custom_value ?: $attributeValue->attributeValue?->value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($product->documents->isNotEmpty())
                <div class="product-docs mt-6">
                    <h3 class="product-tabs__subheading">Downloads</h3>
                    <ul class="product-docs__list">
                        @foreach ($product->documents as $document)
                            <li>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" class="product-docs__link" target="_blank" rel="noopener">
                                    {{ $document->title ?? 'Product document' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section x-show="activeTab === 'reviews'" x-cloak role="tabpanel" class="product-tabs__panel">
            <div class="product-reviews-summary">
                <div class="product-reviews-summary__score">
                    <span class="product-reviews-summary__value">{{ number_format($reviewStats['average'], 1) }}</span>
                    <x-storefront.star-rating :rating="$reviewStats['average']" size="md" />
                    <p class="product-reviews-summary__count">{{ $reviewStats['count'] }} verified reviews</p>
                </div>
            </div>

            @if ($reviews->isNotEmpty())
                <div class="product-reviews-list">
                    @foreach ($reviews as $review)
                        <article class="product-review">
                            <div class="product-review__header">
                                <x-storefront.star-rating :rating="$review->rating" size="sm" />
                                @if ($review->title)
                                    <h3 class="product-review__title">{{ $review->title }}</h3>
                                @endif
                            </div>
                            @if ($review->body)
                                <p class="product-review__body">{{ $review->body }}</p>
                            @endif
                            @if ($review->relationLoaded('images') && $review->images->isNotEmpty())
                                <div class="product-review__images mt-3 flex flex-wrap gap-2">
                                    @foreach ($review->images as $image)
                                        <a href="{{ $image->url }}" target="_blank" rel="noopener" class="block h-16 w-16 overflow-hidden rounded-lg ring-1 ring-ink-100">
                                            <img src="{{ $image->url }}" alt="" class="h-full w-full object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <footer class="product-review__footer">
                                <span class="product-review__author">{{ $review->user?->name ?? 'Verified buyer' }}</span>
                                <span class="product-review__date">{{ $review->created_at?->format('M j, Y') }}</span>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="product-tabs__empty">No reviews yet. Be the first to share your experience after purchase.</p>
            @endif
        </section>

        <section x-show="activeTab === 'shipping'" x-cloak role="tabpanel" class="product-tabs__panel">
            <div class="product-shipping-info">
                <div class="product-shipping-info__item">
                    <h3 class="product-tabs__subheading">Delivery</h3>
                    <p>
                        Orders dispatch within 24–48 hours on in-stock items.
                        @if ($checkoutRules['free_shipping_enabled'] && $freeShippingThreshold > 0)
                            Free shipping on orders over {{ $currencySymbol }} {{ number_format($freeShippingThreshold, 0) }}.
                        @else
                            Standard shipping rates apply at checkout.
                        @endif
                    </p>
                </div>
                <div class="product-shipping-info__item">
                    <h3 class="product-tabs__subheading">Returns</h3>
                    <p>Contact our support team within 7 days if your item arrives damaged or incorrect. We’ll arrange a replacement or refund.</p>
                </div>
                <div class="product-shipping-info__item">
                    <h3 class="product-tabs__subheading">Support</h3>
                    <p>Need installation advice? <a href="{{ route('shop.contact') }}" class="ds-link">Contact our experts</a> before or after purchase.</p>
                </div>
            </div>
        </section>
    </div>
</div>
