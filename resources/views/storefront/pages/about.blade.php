@extends('layouts.storefront')

@section('title', ($page?->meta_title ?: 'About Us').' — '.config('app.name'))
@section('meta_description', $page?->meta_description ?: 'Learn about '.config('app.name').' — your trusted source for premium sanitary ware in Pakistan.')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'About', 'url' => null],
        ]])

        <h1 class="text-3xl md:text-4xl font-bold">{{ $page?->title ?? 'About '.config('app.name') }}</h1>

        <div class="mt-8 prose prose-slate max-w-none text-slate-600 leading-relaxed">
            @if ($page?->content)
                {!! nl2br(e($page->content)) !!}
            @else
                <p>{{ config('app.name') }} is a leading online destination for premium sanitary ware — from elegant basins and mixers to toilets, showers, and bathroom accessories.</p>
                <p>We partner with trusted international and local brands to bring quality products to homeowners, contractors, and developers across Pakistan.</p>
                <h2>Why shop with us?</h2>
                <ul>
                    <li>Curated selection from verified brands</li>
                    <li>Competitive pricing with transparent checkout</li>
                    <li>Cash on delivery and secure online payments</li>
                    <li>Expert customer support for project guidance</li>
                    <li>Reliable delivery and order tracking</li>
                </ul>
                <p>Whether you're renovating a single bathroom or sourcing fixtures for a commercial project, we're here to help you find the right products.</p>
            @endif
        </div>

        <div class="mt-10">
            <a href="{{ route('shop.products.index') }}" class="inline-flex rounded-lg bg-slate-900 text-white px-6 py-3 font-semibold hover:bg-slate-800">Browse products</a>
        </div>
    </div>
@endsection
