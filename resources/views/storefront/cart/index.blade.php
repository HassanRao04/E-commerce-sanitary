@extends('layouts.storefront')

@section('title', 'Cart — '.config('app.name'))

@section('content')
    <div class="commerce-page" data-commerce-cart>
        <div class="ds-container ds-section-tight">
            <x-storefront.checkout-progress current="cart" />

            <div class="commerce-page__header anim-gpu" data-aos="fade-up">
                <h1 class="commerce-page__title">Shopping cart</h1>
                @if ($cart->items->isNotEmpty())
                    <p class="commerce-page__subtitle">{{ $cart->items->sum('quantity') }} items in your cart</p>
                @endif
            </div>

            @if ($cart->items->isEmpty())
                <div class="commerce-empty">
                    <div class="commerce-empty__icon" aria-hidden="true">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h2 class="commerce-empty__title">Your cart is empty</h2>
                    <p class="commerce-empty__text">Browse our collection and add premium fixtures to get started.</p>
                    <a href="{{ route('shop.products.index') }}" class="ds-btn-primary ds-btn-lg mt-6">Continue shopping</a>
                </div>
            @else
                <div class="commerce-layout">
                    <div class="commerce-main">
                        <div class="cart-items" data-cart-items>
                            @foreach ($cart->items as $item)
                                <x-storefront.cart-item :item="$item" />
                            @endforeach
                        </div>

                        <a href="{{ route('shop.products.index') }}" class="commerce-continue-link">
                            ← Continue shopping
                        </a>
                    </div>

                    <aside class="commerce-sidebar commerce-sidebar--sticky anim-gpu" data-aos="fade-left" data-aos-delay="100">
                        @include('storefront.checkout.partials.order-summary', [
                            'cart' => $cart,
                            'totals' => $totals,
                            'pricing' => $pricing,
                            'sticky' => false,
                        ])

                        <a href="{{ route('shop.checkout.index') }}" class="ds-btn-primary ds-btn-lg w-full commerce-checkout-btn">
                            Proceed to checkout
                        </a>

                        <ul class="commerce-trust-list">
                            <li>Secure checkout</li>
                            <li>Multiple payment options</li>
                            <li>Fast nationwide delivery</li>
                        </ul>
                    </aside>
                </div>

                <div class="commerce-mobile-bar lg:hidden" data-mobile-bar>
                    <div>
                        <p class="commerce-mobile-bar__label">Total</p>
                        <p class="commerce-mobile-bar__total" data-mobile-total><x-money :amount="$totals['grand_total']" /></p>
                    </div>
                    <a href="{{ route('shop.checkout.index') }}" class="ds-btn-primary">Checkout</a>
                </div>
            @endif
        </div>
    </div>
@endsection
