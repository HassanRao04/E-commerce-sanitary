@extends('layouts.storefront')

@section('title', 'Page not found — '.config('app.name'))
@section('meta_description', 'The page you requested could not be found.')

@section('content')
    <div class="ds-container ds-section">
        <div class="max-w-xl mx-auto text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-ink-400">404</p>
            <h1 class="ds-heading-1 mt-2">Page not found</h1>
            <p class="ds-body mt-3 text-ink-600">
                The page you are looking for may have been moved, deleted, or never existed.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('shop.home') }}" class="ds-btn-secondary">Go to home</a>
                <a href="{{ route('shop.products.index') }}" class="ds-btn-primary">Browse shop</a>
            </div>
        </div>
    </div>
@endsection
