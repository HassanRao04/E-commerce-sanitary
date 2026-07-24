@props([
    'trust' => [],
])

@php
    $checkoutRules = $checkoutRules ?? [
        'free_shipping_enabled' => false,
        'free_shipping_threshold' => 0,
    ];
    $freeShippingThreshold = (float) ($checkoutRules['free_shipping_threshold'] ?? 0);
    $currencySymbol = config('shop.currency_symbol', 'Rs.');

    $whyChoose = $trust['why_choose'] ?? [];
    $shippingBlock = $trust['shipping'] ?? [];
    $securityBlock = $trust['security'] ?? [];
    $paymentsBlock = $trust['payments'] ?? [];

    $whyChooseUs = $whyChoose['items'] ?? [];
    $shippingBenefits = collect($shippingBlock['items'] ?? [])->map(function (array $item) use ($checkoutRules, $freeShippingThreshold, $currencySymbol): array {
        if (($item['icon'] ?? '') === 'truck') {
            $item['text'] = $checkoutRules['free_shipping_enabled'] && $freeShippingThreshold > 0
                ? "On orders over {$currencySymbol} ".number_format($freeShippingThreshold, 0).' nationwide.'
                : ($item['text'] ?: 'Affordable delivery rates on every order nationwide.');
        }

        return $item;
    })->all();

    $securityBadges = $securityBlock['items'] ?? [];
    $paymentMethods = $paymentsBlock['methods'] ?? [];
@endphp

<div class="trust-sections">
    <section class="trust-section ds-section-tight" aria-labelledby="trust-why-heading">
        <div class="ds-container">
            <header class="trust-section__header anim-gpu" data-aos="fade-up">
                <span class="ds-badge-accent">{{ $whyChoose['badge'] ?? 'Why choose us' }}</span>
                <h2 id="trust-why-heading" class="ds-heading-2 mt-3">{{ $whyChoose['title'] ?? 'Built for quality you can trust' }}</h2>
                <p class="ds-body-sm mt-2 max-w-2xl">{{ $whyChoose['subtitle'] ?? '' }}</p>
            </header>

            <div class="trust-grid trust-grid--4 mt-10" data-gsap-stagger="fade-up" data-gsap-stagger-delay="0.1">
                @foreach ($whyChooseUs as $item)
                    <article class="trust-card anim-gpu" data-gsap-stagger-item>
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

    <section class="trust-section trust-section--muted" aria-labelledby="trust-shipping-heading">
        <div class="ds-container">
            <header class="trust-section__header anim-gpu" data-aos="fade-up">
                <span class="ds-badge-neutral">{{ $shippingBlock['badge'] ?? 'Shipping' }}</span>
                <h2 id="trust-shipping-heading" class="ds-heading-2 mt-3">{{ $shippingBlock['title'] ?? 'Delivery benefits that matter' }}</h2>
                <p class="ds-body-sm mt-2 max-w-2xl">{{ $shippingBlock['subtitle'] ?? '' }}</p>
            </header>

            <div class="trust-shipping-strip mt-10" data-gsap-stagger="slide-right" data-gsap-stagger-delay="0.08">
                @foreach ($shippingBenefits as $item)
                    <article class="trust-shipping-card anim-gpu" data-gsap-stagger-item>
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

    <section class="trust-section trust-section--dark" aria-labelledby="trust-security-heading">
        <div class="ds-container">
            <header class="trust-section__header trust-section__header--light anim-gpu" data-aos="fade-up">
                <span class="ds-badge-neutral !bg-white/10 !text-white !border-white/20">{{ $securityBlock['badge'] ?? 'Security' }}</span>
                <h2 id="trust-security-heading" class="ds-heading-2 mt-3 text-white">{{ $securityBlock['title'] ?? 'Shop with confidence' }}</h2>
                <p class="ds-body-sm mt-2 max-w-2xl !text-ink-300">{{ $securityBlock['subtitle'] ?? '' }}</p>
            </header>

            <div class="trust-security-grid mt-10" data-gsap-stagger="fade-in" data-gsap-stagger-delay="0.12">
                @foreach ($securityBadges as $item)
                    <article class="trust-security-badge anim-gpu" data-gsap-stagger-item>
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

    <section class="trust-section trust-section--muted" aria-labelledby="trust-payments-heading">
        <div class="ds-container">
            <header class="trust-section__header anim-gpu" data-aos="fade-up">
                <span class="ds-badge-accent">{{ $paymentsBlock['badge'] ?? 'Payments' }}</span>
                <h2 id="trust-payments-heading" class="ds-heading-2 mt-3">{{ $paymentsBlock['title'] ?? 'Flexible payment options' }}</h2>
                <p class="ds-body-sm mt-2 max-w-2xl">{{ $paymentsBlock['subtitle'] ?? '' }}</p>
            </header>

            <div class="trust-payments mt-10" data-gsap-stagger="scale" data-gsap-stagger-delay="0.07">
                @foreach ($paymentMethods as $method)
                    <div class="trust-pay-card anim-gpu {{ $method['color'] }}" data-gsap-stagger-item>
                        <span class="trust-pay-card__mark">{{ $method['short'] }}</span>
                        <span class="trust-pay-card__label">{{ $method['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
