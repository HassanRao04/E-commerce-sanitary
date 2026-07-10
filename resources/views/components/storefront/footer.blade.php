@props([
    'categories' => collect(),
])

@php
    use App\Support\StorefrontFooter;
    use App\Support\StorefrontHeader;

    $settings = \App\Models\SiteSetting::current();
    $footer = $storefrontFooter ?? StorefrontFooter::resolved($settings);
    $showFooterSocial = StorefrontHeader::showSocialInFooter($settings);
    $footerCategories = $categories->isNotEmpty()
        ? $categories
        : app(\App\Services\Storefront\StorefrontContentService::class)->footerCategories();

    $newsletterSuccess = session('newsletter_success');
@endphp

<footer class="storefront-footer" aria-label="Site footer">
    <div class="storefront-footer__top">
        <div class="ds-container">
            <div class="storefront-footer__intro anim-gpu" data-aos="fade-up">
                <div class="storefront-footer__brand">
                    <x-storefront.site-logo href="{{ route('shop.home') }}" variant="footer" />
                    <p class="storefront-footer__tagline">
                        {{ $footer['tagline'] ?: ($settings->default_meta_description ?? '') }}
                    </p>

                    @if ($showFooterSocial)
                        <x-storefront.social-links variant="footer" class="storefront-footer__social" />
                    @endif

                    <ul class="storefront-footer__contact">
                        @if (filled($settings->email))
                            <li>
                                <a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a>
                            </li>
                        @endif
                        @if (filled($settings->contact_phone))
                            <li>{{ $settings->contact_phone }}</li>
                        @endif
                        @if (filled($settings->address))
                            <li>{{ $settings->address }}</li>
                        @endif
                    </ul>
                </div>

                <div class="storefront-footer__newsletter">
                    <p class="storefront-footer__heading">{{ $footer['newsletter']['title'] ?? 'Newsletter' }}</p>
                    <p class="storefront-footer__newsletter-copy">{{ $footer['newsletter']['copy'] ?? '' }}</p>

                    @if ($newsletterSuccess)
                        <div class="storefront-footer__newsletter-success" role="status">
                            {{ $newsletterSuccess }}
                        </div>
                    @else
                        <form
                            method="POST"
                            action="{{ route('shop.newsletter.store') }}"
                            class="storefront-footer__newsletter-form"
                            x-data="{ submitting: false }"
                            @submit="submitting = true"
                        >
                            @csrf
                            <label for="footer-newsletter-email" class="sr-only">Email address</label>
                            <input
                                type="email"
                                id="footer-newsletter-email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                placeholder="Your email address"
                                class="storefront-footer__newsletter-input"
                                @class(['storefront-footer__newsletter-input--error' => $errors->has('email')])
                            >
                            <button type="submit" class="storefront-footer__newsletter-btn" :disabled="submitting">
                                <span x-show="!submitting">Subscribe</span>
                                <span x-show="submitting" x-cloak>…</span>
                            </button>
                        </form>
                        @error('email')
                            <p class="storefront-footer__newsletter-error">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="storefront-footer__links">
        <div class="ds-container">
            <div class="storefront-footer__grid">
                @foreach ($footer['columns'] as $column)
                    <div class="storefront-footer__column">
                        <p class="storefront-footer__heading">{{ $column['heading'] }}</p>
                        <ul class="storefront-footer__list">
                            @foreach ($column['links'] as $link)
                                <li><a href="{{ StorefrontFooter::linkUrl($link) }}">{{ $link['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <div class="storefront-footer__column">
                    <p class="storefront-footer__heading">Categories</p>
                    <ul class="storefront-footer__list">
                        @forelse ($footerCategories as $category)
                            <li>
                                <a href="{{ route('shop.categories.show', $category) }}">{{ $category->name }}</a>
                            </li>
                        @empty
                            <li><a href="{{ route('shop.products.index') }}">Browse all products</a></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="storefront-footer__bottom">
        <div class="ds-container storefront-footer__bottom-inner">
            <p>&copy; {{ date('Y') }} {{ $footer['copyright_name'] ?? $settings->displayName() }}. All rights reserved.</p>
            @if (filled($footer['bottom_meta'] ?? null))
                <p class="storefront-footer__bottom-meta">{{ $footer['bottom_meta'] }}</p>
            @endif
        </div>
    </div>
</footer>
