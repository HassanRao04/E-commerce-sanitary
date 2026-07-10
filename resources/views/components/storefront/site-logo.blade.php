@props([
    'href' => route('shop.home'),
    'variant' => 'header',
    'class' => '',
])

@php
    $settings = \App\Models\SiteSetting::current();
    $isFooter = $variant === 'footer';
    $linkClass = trim('storefront-site-logo '.($isFooter ? 'storefront-site-logo--footer ' : '').$class);
    $imageClass = 'storefront-site-logo__image'.($isFooter ? ' storefront-site-logo__image--footer' : '');
@endphp

<a href="{{ $href }}" class="{{ $linkClass }}">
    @if ($settings->logo_url)
        <img
            src="{{ $settings->logo_url }}"
            alt="{{ $settings->displayName() }}"
            class="{{ $imageClass }}"
            width="160"
            height="40"
            decoding="async"
        >
    @else
        <span class="storefront-site-logo__text">{{ $settings->displayName() }}</span>
    @endif
</a>
