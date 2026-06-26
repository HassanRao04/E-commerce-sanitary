@extends('layouts.storefront')

@section('title', 'Checkout — '.config('app.name'))
@section('meta_description', 'Complete your order securely at '.config('app.name').'.')

@section('content')
    <div class="commerce-page commerce-page--checkout">
        <div class="ds-container ds-section-tight">
            <x-storefront.checkout-progress current="checkout" />

            <div class="commerce-page__header anim-gpu" data-aos="fade-up">
                <h1 class="commerce-page__title">Checkout</h1>
                <p class="commerce-page__subtitle">
                    @auth
                        Signed in as {{ auth()->user()->email }}.
                    @else
                        Checking out as a guest.
                        <a href="{{ route('login', ['redirect' => route('shop.checkout.index')]) }}" class="ds-link font-medium">Log in</a>
                        for faster checkout.
                    @endauth
                </p>
            </div>

            @php
                $alwaysShowShippingFields = ! auth()->check() || $shippingAddresses->isEmpty();
                $alwaysShowBillingFields = ! auth()->check() || $billingAddresses->isEmpty();
            @endphp

            <form
                id="checkout-form"
                action="{{ route('shop.checkout.store') }}"
                method="POST"
                class="commerce-layout commerce-layout--checkout"
                x-data="{ billingSameAsShipping: {{ old('billing_same_as_shipping', true) ? 'true' : 'false' }}, useNewShipping: {{ old('shipping_address_id') === '' ? 'true' : 'false' }}, useNewBilling: {{ old('billing_address_id') === '' ? 'true' : 'false' }} }"
            >
                @csrf

                <div class="commerce-main commerce-main--checkout">
                    <section class="checkout-step">
                        <div class="checkout-step__head">
                            <span class="checkout-step__number">1</span>
                            <h2 class="checkout-step__title">Contact details</h2>
                        </div>
                        <div class="checkout-step__body">
                            <div class="checkout-fields">
                                <div class="ds-field">
                                    <label for="customer_name" class="ds-label">Full name</label>
                                    <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required class="ds-input @error('customer_name') ds-input-error @enderror">
                                    @error('customer_name')<p class="ds-error-text">{{ $message }}</p>@enderror
                                </div>
                                <div class="ds-field">
                                    <label for="customer_phone" class="ds-label">Phone</label>
                                    <input id="customer_phone" type="text" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required class="ds-input @error('customer_phone') ds-input-error @enderror">
                                    @error('customer_phone')<p class="ds-error-text">{{ $message }}</p>@enderror
                                </div>
                                <div class="ds-field checkout-fields__full">
                                    <label for="customer_email" class="ds-label">Email</label>
                                    <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email', auth()->user()?->email) }}" required class="ds-input @error('customer_email') ds-input-error @enderror">
                                    @error('customer_email')<p class="ds-error-text">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="checkout-step">
                        <div class="checkout-step__head">
                            <span class="checkout-step__number">2</span>
                            <h2 class="checkout-step__title">Shipping address</h2>
                        </div>
                        <div class="checkout-step__body space-y-4">
                            @auth
                                @if ($shippingAddresses->isNotEmpty())
                                    <div class="checkout-address-list">
                                        @foreach ($shippingAddresses as $address)
                                            <label class="checkout-address-card">
                                                <input type="radio" name="shipping_address_id" value="{{ $address->id }}" class="ds-radio" @checked(old('shipping_address_id', $loop->first ? $address->id : null) == $address->id) @click="useNewShipping = false">
                                                <span class="checkout-address-card__content">
                                                    <span class="checkout-address-card__name">{{ $address->full_name }}</span>
                                                    <span class="checkout-address-card__lines">
                                                        {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                                        {{ $address->city }}@if ($address->state), {{ $address->state }}@endif
                                                        {{ $address->postal_code }} · {{ $address->country }}
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                        <label class="checkout-address-card checkout-address-card--inline">
                                            <input type="radio" name="shipping_address_id" value="" class="ds-radio" @checked(old('shipping_address_id') === '') @click="useNewShipping = true">
                                            <span class="checkout-address-card__content">Use a new shipping address</span>
                                        </label>
                                    </div>
                                @endif
                            @endauth

                            <div x-show="useNewShipping || {{ $alwaysShowShippingFields ? 'true' : 'false' }}" x-cloak>
                                @include('storefront.checkout.partials.address-fields', ['prefix' => 'shipping_'])
                            </div>
                        </div>
                    </section>

                    <section class="checkout-step">
                        <div class="checkout-step__head">
                            <span class="checkout-step__number">3</span>
                            <h2 class="checkout-step__title">Billing address</h2>
                        </div>
                        <div class="checkout-step__body space-y-4">
                            <label class="checkout-checkbox">
                                <input type="hidden" name="billing_same_as_shipping" value="0">
                                <input type="checkbox" name="billing_same_as_shipping" value="1" class="ds-checkbox" x-model="billingSameAsShipping" @checked(old('billing_same_as_shipping', true))>
                                Same as shipping address
                            </label>

                            <div x-show="!billingSameAsShipping" x-cloak class="space-y-4">
                                @auth
                                    @if ($billingAddresses->isNotEmpty())
                                        <div class="checkout-address-list">
                                            @foreach ($billingAddresses as $address)
                                                <label class="checkout-address-card">
                                                    <input type="radio" name="billing_address_id" value="{{ $address->id }}" class="ds-radio" @checked(old('billing_address_id') == $address->id) @click="useNewBilling = false">
                                                    <span class="checkout-address-card__content">
                                                        <span class="checkout-address-card__name">{{ $address->full_name }}</span>
                                                        <span class="checkout-address-card__lines">{{ $address->line1 }}, {{ $address->city }}, {{ $address->country }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                            <label class="checkout-address-card checkout-address-card--inline">
                                                <input type="radio" name="billing_address_id" value="" class="ds-radio" @checked(old('billing_address_id') === '') @click="useNewBilling = true">
                                                <span class="checkout-address-card__content">Use a new billing address</span>
                                            </label>
                                        </div>
                                    @endif
                                @endauth

                                <div x-show="useNewBilling || {{ $alwaysShowBillingFields ? 'true' : 'false' }}">
                                    @include('storefront.checkout.partials.address-fields', ['prefix' => 'billing_'])
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="checkout-step">
                        <div class="checkout-step__head">
                            <span class="checkout-step__number">4</span>
                            <h2 class="checkout-step__title">Payment &amp; notes</h2>
                        </div>
                        <div class="checkout-step__body space-y-4">
                            <div class="checkout-payment-list">
                                @foreach ($paymentMethods as $method)
                                    <label class="checkout-payment-card">
                                        <input type="radio" name="payment_method" value="{{ $method->value }}" class="ds-radio" @checked(old('payment_method', $paymentMethods[0]->value ?? '') === $method->value) required>
                                        <span class="checkout-payment-card__label">{{ str($method->name)->headline()->replace('_', ' ') }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('payment_method')<p class="ds-error-text">{{ $message }}</p>@enderror

                            <div class="ds-field">
                                <label for="notes" class="ds-label">Order notes (optional)</label>
                                <textarea id="notes" name="notes" rows="3" class="ds-textarea" placeholder="Delivery instructions, gate code, etc.">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="commerce-sidebar commerce-sidebar--sticky commerce-sidebar--checkout">
                    @include('storefront.checkout.partials.order-summary', [
                        'cart' => $cart,
                        'totals' => $totals,
                        'pricing' => $pricing,
                        'sticky' => false,
                    ])

                    <button type="submit" class="ds-btn-primary ds-btn-lg w-full commerce-checkout-btn">
                        <span class="block">Place order</span>
                        <span class="block text-sm font-semibold opacity-90 mt-0.5"><x-money :amount="$totals['grand_total']" /></span>
                    </button>
                </aside>
            </form>

            <div class="commerce-mobile-bar lg:hidden">
                <div>
                    <p class="commerce-mobile-bar__label">Total</p>
                    <p class="commerce-mobile-bar__total"><x-money :amount="$totals['grand_total']" /></p>
                </div>
                <button type="submit" form="checkout-form" class="ds-btn-primary">Place order</button>
            </div>
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
@endsection
