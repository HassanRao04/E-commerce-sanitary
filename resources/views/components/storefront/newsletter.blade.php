@props([
    'title' => 'Unlock exclusive offers',
    'subtitle' => 'Join our newsletter for early access to sales, new arrivals, and expert bathroom inspiration.',
    'offer' => '10% off your first order',
    'offerCode' => 'WELCOME10',
    'action' => null,
    'theme' => 'dark',
])

@php
    $formAction = $action ?? route('shop.newsletter.store');
    $successMessage = session('newsletter_success');
    $hasSuccess = filled($successMessage);
@endphp

<section
    class="newsletter-section ds-section-tight"
    aria-labelledby="newsletter-heading"
    data-newsletter
>
    <div class="ds-container">
        <div @class([
            'newsletter-panel',
            'newsletter-panel--dark' => $theme === 'dark',
            'newsletter-panel--light' => $theme === 'light',
        ])>
            <div class="newsletter-panel__bg" aria-hidden="true">
                <span class="newsletter-panel__orb newsletter-panel__orb--1"></span>
                <span class="newsletter-panel__orb newsletter-panel__orb--2"></span>
                <span class="newsletter-panel__orb newsletter-panel__orb--3"></span>
                <span class="newsletter-panel__grid"></span>
            </div>

            <div class="newsletter-panel__content">
                <div class="newsletter-panel__copy anim-gpu" data-aos="fade-up">
                    @if ($offer)
                        <div class="newsletter-offer">
                            <span class="newsletter-offer__badge">Limited offer</span>
                            <p class="newsletter-offer__text">
                                {{ $offer }}
                                @if ($offerCode)
                                    — use code <strong class="newsletter-offer__code">{{ $offerCode }}</strong>
                                @endif
                            </p>
                        </div>
                    @endif

                    <h2 id="newsletter-heading" class="newsletter-panel__title">{{ $title }}</h2>
                    <p class="newsletter-panel__subtitle">{{ $subtitle }}</p>

                    <ul class="newsletter-benefits" aria-label="Newsletter benefits">
                        <li>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Early access to flash sales
                        </li>
                        <li>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            New product drops first
                        </li>
                        <li>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Unsubscribe anytime
                        </li>
                    </ul>
                </div>

                <div class="newsletter-panel__form-wrap anim-gpu" data-aos="fade-left" data-aos-delay="150">
                    @if ($hasSuccess)
                        <div class="newsletter-success" role="status">
                            <div class="newsletter-success__icon">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="newsletter-success__title">You're subscribed!</p>
                                <p class="newsletter-success__text">{{ $successMessage }}</p>
                            </div>
                        </div>
                    @else
                        <form
                            method="POST"
                            action="{{ $formAction }}"
                            class="newsletter-form"
                            x-data="{ submitting: false }"
                            @submit="submitting = true"
                        >
                            @csrf

                            <label for="newsletter-email" class="sr-only">Email address</label>
                            <div class="newsletter-form__row">
                                <input
                                    type="email"
                                    id="newsletter-email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                    inputmode="email"
                                    placeholder="Enter your email"
                                    class="newsletter-form__input ds-input"
                                    @class(['!border-danger' => $errors->has('email')])
                                >
                                <button
                                    type="submit"
                                    class="newsletter-form__submit ds-btn-primary"
                                    :disabled="submitting"
                                >
                                    <span x-show="!submitting">Get my offer</span>
                                    <span x-show="submitting" x-cloak>Joining…</span>
                                </button>
                            </div>

                            @error('email')
                                <p class="newsletter-form__error">{{ $message }}</p>
                            @enderror

                            <p class="newsletter-form__fine-print">
                                By subscribing you agree to receive marketing emails. No spam — promise.
                            </p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
