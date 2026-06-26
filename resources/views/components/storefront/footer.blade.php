@props([
    'categories' => collect(),
])

@php
    $settings = \App\Models\SiteSetting::current();
    $socialLinks = collect($settings->social_links ?? [])->filter();
    $footerCategories = $categories->isNotEmpty()
        ? $categories
        : \App\Models\Category::query()->active()->roots()->ordered()->limit(6)->get();

    $shopLinks = [
        ['label' => 'All products', 'url' => route('shop.products.index')],
        ['label' => 'New arrivals', 'url' => route('shop.products.index', ['collection' => 'new'])],
        ['label' => 'Best sellers', 'url' => route('shop.products.index', ['collection' => 'best-sellers'])],
        ['label' => 'Flash sale', 'url' => route('shop.products.index', ['collection' => 'sale'])],
        ['label' => 'Wishlist', 'url' => route('shop.wishlist.index')],
        ['label' => 'Shopping cart', 'url' => route('shop.cart.index')],
    ];

    $supportLinks = [
        ['label' => 'Contact us', 'url' => route('shop.contact')],
        ['label' => 'Track order', 'url' => route('shop.orders.track')],
        ['label' => 'My account', 'url' => route('shop.account.dashboard')],
        ['label' => 'About us', 'url' => route('shop.about')],
        ['label' => 'Checkout', 'url' => route('shop.checkout.index')],
    ];

    $policyLinks = [
        ['label' => 'Shipping policy', 'url' => route('shop.contact')],
        ['label' => 'Returns & refunds', 'url' => route('shop.contact')],
        ['label' => 'Privacy policy', 'url' => route('shop.contact')],
        ['label' => 'Terms of service', 'url' => route('shop.contact')],
    ];

    $socialProfiles = [
        'facebook' => ['label' => 'Facebook', 'icon' => 'facebook'],
        'instagram' => ['label' => 'Instagram', 'icon' => 'instagram'],
        'youtube' => ['label' => 'YouTube', 'icon' => 'youtube'],
        'twitter' => ['label' => 'X (Twitter)', 'icon' => 'twitter'],
    ];

    $newsletterSuccess = session('newsletter_success');
@endphp

<footer class="storefront-footer" aria-label="Site footer">
    <div class="storefront-footer__top">
        <div class="ds-container">
            <div class="storefront-footer__intro anim-gpu" data-aos="fade-up">
                <div class="storefront-footer__brand">
                    <a href="{{ route('shop.home') }}" class="storefront-footer__logo">
                        {{ config('app.name', 'Sanitary Store') }}
                    </a>
                    <p class="storefront-footer__tagline">
                        {{ $settings->default_meta_description ?? 'Premium sanitary ware for homes, offices, and commercial projects across Pakistan.' }}
                    </p>

                    @if ($socialLinks->isNotEmpty() || filled($settings->whatsapp))
                        <div class="storefront-footer__social" aria-label="Social links">
                            @foreach ($socialProfiles as $key => $profile)
                                @if (filled($socialLinks[$key] ?? null))
                                    <a
                                        href="{{ $socialLinks[$key] }}"
                                        class="storefront-footer__social-link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="{{ $profile['label'] }}"
                                    >
                                        @include('storefront.partials.footer.social-icon', ['icon' => $profile['icon']])
                                    </a>
                                @endif
                            @endforeach

                            @if (filled($settings->whatsapp))
                                <a
                                    href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->whatsapp) }}"
                                    class="storefront-footer__social-link storefront-footer__social-link--whatsapp"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="WhatsApp"
                                >
                                    @include('storefront.partials.footer.social-icon', ['icon' => 'whatsapp'])
                                </a>
                            @endif
                        </div>
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
                    <p class="storefront-footer__heading">Newsletter</p>
                    <p class="storefront-footer__newsletter-copy">Get 10% off your first order. Exclusive deals, no spam.</p>

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
                <div class="storefront-footer__column">
                    <p class="storefront-footer__heading">Company</p>
                    <ul class="storefront-footer__list">
                        <li><a href="{{ route('shop.about') }}">About us</a></li>
                        <li><a href="{{ route('shop.contact') }}">Contact</a></li>
                        <li><a href="{{ route('shop.home') }}">Storefront</a></li>
                    </ul>
                </div>

                <div class="storefront-footer__column">
                    <p class="storefront-footer__heading">Shop</p>
                    <ul class="storefront-footer__list">
                        @foreach ($shopLinks as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

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

                <div class="storefront-footer__column">
                    <p class="storefront-footer__heading">Support</p>
                    <ul class="storefront-footer__list">
                        @foreach ($supportLinks as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div class="storefront-footer__column">
                    <p class="storefront-footer__heading">Policies</p>
                    <ul class="storefront-footer__list">
                        @foreach ($policyLinks as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="storefront-footer__bottom">
        <div class="ds-container storefront-footer__bottom-inner">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="storefront-footer__bottom-meta">Secure checkout · Cash on delivery · Fast nationwide shipping</p>
        </div>
    </div>
</footer>
