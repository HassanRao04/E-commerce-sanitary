@extends('layouts.storefront')

@section('title', ($address->exists ? 'Edit' : 'Add').' Address — '.config('app.name'))

@section('content')
    <div class="ds-container ds-section max-w-2xl">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => route('shop.account.dashboard')],
            ['label' => 'Addresses', 'url' => route('shop.account.addresses.index')],
            ['label' => $address->exists ? 'Edit' : 'Add', 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1 min-w-0">
                <h1 class="ds-heading-2 mb-6">{{ $address->exists ? 'Edit address' : 'Add address' }}</h1>

                <form
                    method="POST"
                    action="{{ $address->exists ? route('shop.account.addresses.update', $address) : route('shop.account.addresses.store') }}"
                    class="ds-card ds-card-body space-y-4"
                >
                    @csrf
                    @if ($address->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <label for="type" class="ds-label">Address type</label>
                        <select id="type" name="type" class="ds-input mt-1 w-full" required>
                            @foreach (\App\Enums\AddressType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('type', $address->type?->value) === $type->value)>{{ str($type->value)->headline() }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="full_name" class="ds-label">Full name</label>
                            <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $address->full_name) }}" class="ds-input mt-1 w-full" required>
                            @error('full_name')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="ds-label">Phone</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $address->phone) }}" class="ds-input mt-1 w-full" required>
                            @error('phone')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="line1" class="ds-label">Address line 1</label>
                        <input id="line1" type="text" name="line1" value="{{ old('line1', $address->line1) }}" class="ds-input mt-1 w-full" required>
                        @error('line1')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="line2" class="ds-label">Address line 2</label>
                        <input id="line2" type="text" name="line2" value="{{ old('line2', $address->line2) }}" class="ds-input mt-1 w-full">
                        @error('line2')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="ds-label">City</label>
                            <input id="city" type="text" name="city" value="{{ old('city', $address->city) }}" class="ds-input mt-1 w-full" required>
                            @error('city')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="state" class="ds-label">State / Province</label>
                            <input id="state" type="text" name="state" value="{{ old('state', $address->state) }}" class="ds-input mt-1 w-full">
                            @error('state')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="postal_code" class="ds-label">Postal code</label>
                            <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}" class="ds-input mt-1 w-full">
                            @error('postal_code')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="country" class="ds-label">Country</label>
                            <input id="country" type="text" name="country" value="{{ old('country', $address->country ?? 'Pakistan') }}" class="ds-input mt-1 w-full" required>
                            @error('country')<p class="text-danger text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1" class="ds-checkbox" @checked(old('is_default', $address->is_default))>
                        <span class="ds-body-sm">Set as default address</span>
                    </label>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="ds-btn-primary">Save address</button>
                        <a href="{{ route('shop.account.addresses.index') }}" class="ds-btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
