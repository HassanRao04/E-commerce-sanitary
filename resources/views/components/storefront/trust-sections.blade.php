@props([])

@php
    $freeShippingThreshold = (float) config('shop.free_shipping_threshold', 10000);
    $currencySymbol = config('shop.currency_symbol', 'Rs.');

    $whyChooseUs = [
        ['icon' => 'sparkles', 'title' => 'Premium quality', 'text' => 'Curated sanitary ware from trusted international and local brands.'],
        ['icon' => 'headset', 'title' => 'Expert support', 'text' => 'Our specialists help you choose the right fixtures for every project.'],
        ['icon' => 'badge-check', 'title' => 'Authorized dealer', 'text' => 'Genuine products with manufacturer-backed warranties.'],
        ['icon' => 'shield-check', 'title' => 'Hassle-free returns', 'text' => 'Simple return process if something is not right with your order.'],
    ];

    $shippingBenefits = [
        ['icon' => 'truck', 'title' => 'Free shipping', 'text' => "On orders over {$currencySymbol} ".number_format($freeShippingThreshold, 0).' nationwide.'],
        ['icon' => 'clock', 'title' => 'Fast delivery', 'text' => 'Dispatch within 24–48 hours on in-stock items across major cities.'],
        ['icon' => 'box', 'title' => 'Secure packaging', 'text' => 'Every item is carefully packed to arrive in perfect condition.'],
        ['icon' => 'map-pin', 'title' => 'Live tracking', 'text' => 'Track your order from warehouse to doorstep with SMS updates.'],
    ];

    $securityBadges = [
        ['icon' => 'lock-closed', 'title' => 'SSL encrypted', 'text' => '256-bit encryption protects your data at checkout.'],
        ['icon' => 'shield-check', 'title' => 'Secure checkout', 'text' => 'PCI-compliant payment processing you can trust.'],
        ['icon' => 'user-group', 'title' => 'Privacy first', 'text' => 'We never sell your personal information to third parties.'],
    ];

    $paymentMethods = [
        ['label' => 'Cash on Delivery', 'short' => 'COD', 'color' => 'trust-pay--cod'],
        ['label' => 'JazzCash', 'short' => 'JC', 'color' => 'trust-pay--jazzcash'],
        ['label' => 'Easypaisa', 'short' => 'EP', 'color' => 'trust-pay--easypaisa'],
        ['label' => 'Bank Transfer', 'short' => 'BT', 'color' => 'trust-pay--bank'],
        ['label' => 'Card / Stripe', 'short' => 'Card', 'color' => 'trust-pay--card'],
    ];
@endphp

<div class="trust-sections" data-trust-sections>
    {{-- 1. Why Choose Us --}}
    <section class="trust-section ds-section-tight" aria-labelledby="trust-why-heading">
        <div class="ds-container">
            <header class="trust-section__header anim-gpu" data-aos="fade-up">
                <span class="ds-badge-accent">Why choose us</span>
                <h2 id="trust-why-heading" class="ds-heading-2 mt-3">Built for quality you can trust</h2>
                <p class="ds-body-sm mt-2 max-w-2xl">Everything we do is designed to make buying premium sanitary ware simple, safe, and satisfying.</p>
            </header>

            <div class="trust-grid trust-grid--4 mt-10" data-gsap-stagger="fade-up" data-gsap-stagger-delay="0.1">
                @foreach ($whyChooseUs as $item)
                    <article class="trust-card anim-gpu" data-gsap-stagger-item data-gsap-hover="lift">
                        <div class="trust-card__icon-wrap">
                            <x-storefront.trust.icon :name="$item['icon']" class="trust-card__icon" />
                        </div>
                        <h3 class="trust-card__title">{{ $item['title'] }}</h3>
                        <p class="trust-card__text">{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 2. Shipping Benefits --}}
    <section class="trust-section trust-section--muted" aria-labelledby="trust-shipping-heading">
        <div class="ds-container">
            <header class="trust-section__header anim-gpu" data-aos="fade-up">
                <span class="ds-badge-neutral">Shipping</span>
                <h2 id="trust-shipping-heading" class="ds-heading-2 mt-3">Delivery benefits that matter</h2>
                <p class="ds-body-sm mt-2 max-w-2xl">From free shipping thresholds to careful packaging — your order is in good hands.</p>
            </header>

            <div class="trust-shipping-strip mt-10" data-gsap-stagger="slide-right" data-gsap-stagger-delay="0.08">
                @foreach ($shippingBenefits as $item)
                    <article class="trust-shipping-card anim-gpu" data-gsap-stagger-item data-gsap-hover="glow">
                        <div class="trust-shipping-card__icon">
                            <x-storefront.trust.icon :name="$item['icon']" class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="trust-shipping-card__title">{{ $item['title'] }}</h3>
                            <p class="trust-shipping-card__text">{{ $item['text'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 3. Security Badges --}}
    <section class="trust-section trust-section--dark" aria-labelledby="trust-security-heading">
        <div class="ds-container">
            <header class="trust-section__header trust-section__header--light anim-gpu" data-aos="fade-up">
                <span class="ds-badge-neutral !bg-white/10 !text-white !border-white/20">Security</span>
                <h2 id="trust-security-heading" class="ds-heading-2 mt-3 text-white">Shop with confidence</h2>
                <p class="ds-body-sm mt-2 max-w-2xl !text-ink-300">Your payment details and personal data are protected at every step.</p>
            </header>

            <div class="trust-security-grid mt-10" data-gsap-stagger="fade-in" data-gsap-stagger-delay="0.12">
                @foreach ($securityBadges as $item)
                    <article class="trust-security-badge anim-gpu" data-gsap-stagger-item data-gsap-hover="scale">
                        <div class="trust-security-badge__icon">
                            <x-storefront.trust.icon :name="$item['icon']" class="h-6 w-6 text-white" />
                        </div>
                        <h3 class="trust-security-badge__title">{{ $item['title'] }}</h3>
                        <p class="trust-security-badge__text">{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Payment Methods --}}
    <section class="trust-section trust-section--muted" aria-labelledby="trust-payments-heading">
        <div class="ds-container">
            <header class="trust-section__header anim-gpu" data-aos="fade-up">
                <span class="ds-badge-accent">Payments</span>
                <h2 id="trust-payments-heading" class="ds-heading-2 mt-3">Flexible payment options</h2>
                <p class="ds-body-sm mt-2 max-w-2xl">Pay the way that suits you — online, mobile wallet, bank transfer, or cash on delivery.</p>
            </header>

            <div class="trust-payments mt-10" data-gsap-stagger="scale" data-gsap-stagger-delay="0.07">
                @foreach ($paymentMethods as $method)
                    <div class="trust-pay-card anim-gpu {{ $method['color'] }}" data-gsap-stagger-item data-gsap-hover="lift">
                        <span class="trust-pay-card__mark">{{ $method['short'] }}</span>
                        <span class="trust-pay-card__label">{{ $method['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
