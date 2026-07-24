@extends('layouts.storefront')

@section('title', 'Wishlist — '.config('app.name'))
@section('meta_description', 'View and manage your saved products at '.config('app.name').'.')

@section('content')
    <div class="ds-container ds-section-tight">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'Wishlist', 'url' => null],
        ]])

        <h1 class="ds-heading-1 mb-8">My wishlist</h1>

        @if ($items->isEmpty())
            <div class="ds-card ds-card-body text-center py-12">
                <p class="ds-body">Your wishlist is empty.</p>
                <a href="{{ route('shop.products.index') }}" class="ds-btn-primary ds-btn-sm mt-4">Browse products</a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($items as $item)
                    @if ($item->product)
                        <div class="relative">
                            <x-storefront.product-card :product="$item->product" :in-wishlist="true" />
                            <form action="{{ route('shop.wishlist.destroy', $item->product_id) }}" method="POST" class="absolute top-3 right-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ds-btn-icon !h-9 !w-9 !bg-surface/90 hover:!text-danger" aria-label="Remove from wishlist">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endsection
