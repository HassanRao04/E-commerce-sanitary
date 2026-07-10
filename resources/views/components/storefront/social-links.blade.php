@props([
    'variant' => 'footer',
])

@php
    use App\Support\SocialLinks;

    $links = SocialLinks::visible();
@endphp

@if ($links->isNotEmpty())
    <div
        @class([
            'storefront-social-links',
            'storefront-social-links--top-bar' => $variant === 'top-bar',
            'storefront-social-links--footer' => $variant === 'footer',
        ])
        aria-label="Social links"
        {{ $attributes }}
    >
        @foreach ($links as $link)
            <a
                href="{{ $link['url'] }}"
                @class([
                    'storefront-social-links__item',
                    'storefront-social-links__item--whatsapp' => $link['key'] === 'whatsapp',
                ])
                target="_blank"
                rel="noopener noreferrer"
                aria-label="{{ $link['label'] }}"
            >
                <x-storefront.social-icon :icon="$link['icon']" />
            </a>
        @endforeach
    </div>
@endif
