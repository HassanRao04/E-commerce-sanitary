@extends('layouts.storefront')

@section('title', 'My Addresses — '.config('app.name'))

@section('content')
    <div class="ds-container ds-section">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => route('shop.account.dashboard')],
            ['label' => 'Addresses', 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="ds-heading-2">My addresses</h1>
                    <a href="{{ route('shop.account.addresses.create') }}" class="ds-btn-primary">Add address</a>
                </div>

                @if ($addresses->isEmpty())
                    <div class="ds-card ds-card-body text-center py-12">
                        <p class="ds-body text-ink-600">No saved addresses yet.</p>
                        <a href="{{ route('shop.account.addresses.create') }}" class="ds-btn-primary mt-4 inline-flex">Add your first address</a>
                    </div>
                @else
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach ($addresses as $address)
                            <div class="ds-card ds-card-body">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        @if ($address->is_default)
                                            <span class="inline-flex mb-2 rounded-pill bg-accent/10 px-2 py-0.5 text-xs font-medium text-accent">Default</span>
                                        @endif
                                        <p class="font-semibold text-ink">{{ $address->full_name }}</p>
                                        <p class="ds-body-sm text-ink-500 capitalize">{{ $address->type->value }} address</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ route('shop.account.addresses.edit', $address) }}" class="ds-link ds-body-sm">Edit</a>
                                        <form method="POST" action="{{ route('shop.account.addresses.destroy', $address) }}" onsubmit="return confirm('Remove this address?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ds-link ds-body-sm !text-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                <p class="ds-body-sm mt-3">{{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif</p>
                                <p class="ds-body-sm">{{ $address->city }}@if ($address->state), {{ $address->state }}@endif</p>
                                <p class="ds-body-sm">{{ $address->country }} @if ($address->postal_code){{ $address->postal_code }}@endif</p>
                                <p class="ds-body-sm mt-2 text-ink-500">{{ $address->phone }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
