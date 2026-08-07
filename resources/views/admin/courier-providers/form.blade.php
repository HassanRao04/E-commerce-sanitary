@extends('layouts.admin')

@section('title', $provider->exists ? 'Edit Courier Provider' : 'Create Courier Provider')

@section('content')
    @include('admin.partials.page-header', ['title' => $provider->exists ? 'Edit Courier Provider' : 'Create Courier Provider'])

    <form
        method="POST"
        action="{{ $provider->exists ? route('admin.courier-providers.update', $provider) : route('admin.courier-providers.store') }}"
        enctype="multipart/form-data"
        class="bg-white rounded-lg shadow p-6 space-y-6 max-w-3xl"
    >
        @csrf
        @if ($provider->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $provider->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="slug" value="Slug (optional)" />
                <x-text-input id="slug" name="slug" class="block mt-1 w-full" :value="old('slug', $provider->slug)" @readonly($provider->slug === 'manual') />
                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="logo" value="Logo" />
            @if ($provider->logo_url)
                <div class="mt-2 mb-3 flex items-center gap-4">
                    <img src="{{ $provider->logo_url }}" alt="" class="h-12 w-12 rounded object-contain border border-gray-200">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Remove current logo
                    </label>
                </div>
            @endif
            <input id="logo" type="file" name="logo" accept="image/*" class="block mt-1 w-full text-sm text-gray-600">
            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        </div>

        <div class="rounded-xl border border-gray-200 p-5 space-y-5">
            <h3 class="font-semibold text-gray-900">API Configuration</h3>
            <p class="text-sm text-gray-500">Credentials are stored encrypted. Leave secret fields blank on update to keep existing values.</p>

            <div>
                <x-input-label for="api_base_url" value="API Base URL" />
                <x-text-input id="api_base_url" name="api_base_url" type="url" class="block mt-1 w-full" :value="old('api_base_url', $provider->api_base_url)" placeholder="https://api.example.com" />
                <x-input-error :messages="$errors->get('api_base_url')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="api_key" value="API Key" />
                    <x-text-input id="api_key" name="api_key" class="block mt-1 w-full" :value="old('api_key')" placeholder="{{ $provider->exists && $provider->hasApiCredentials() ? 'Saved — leave blank to keep' : '' }}" autocomplete="off" />
                    <x-input-error :messages="$errors->get('api_key')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="api_secret" value="API Secret / Token" />
                    <x-text-input id="api_secret" name="api_secret" type="password" class="block mt-1 w-full" :value="old('api_secret')" placeholder="{{ $provider->exists && $provider->api_secret ? 'Saved — leave blank to keep' : '' }}" autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('api_secret')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="account_number" value="Account Number" />
                <x-text-input id="account_number" name="account_number" class="block mt-1 w-full" :value="old('account_number', $provider->account_number)" />
                <x-input-error :messages="$errors->get('account_number')" class="mt-2" />
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 p-5 space-y-5">
            <h3 class="font-semibold text-gray-900">Pickup & Defaults</h3>

            <div>
                <x-input-label for="pickup_address" value="Pickup Address" />
                <textarea id="pickup_address" name="pickup_address" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('pickup_address', $provider->pickup_address) }}</textarea>
                <x-input-error :messages="$errors->get('pickup_address')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="pickup_city" value="Pickup City" />
                    <x-text-input id="pickup_city" name="pickup_city" class="block mt-1 w-full" :value="old('pickup_city', $provider->pickup_city)" />
                    <x-input-error :messages="$errors->get('pickup_city')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="default_package_weight" value="Default Package Weight (kg)" />
                    <x-text-input id="default_package_weight" name="default_package_weight" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('default_package_weight', $provider->default_package_weight)" />
                    <x-input-error :messages="$errors->get('default_package_weight')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="tracking_url_template" value="Tracking URL Template" />
                <x-text-input id="tracking_url_template" name="tracking_url_template" class="block mt-1 w-full" :value="old('tracking_url_template', $provider->tracking_url_template)" placeholder="https://example.com/track/{tracking_number}" />
                <x-input-error :messages="$errors->get('tracking_url_template')" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $provider->is_active))> Active
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_sandbox" value="1" @checked(old('is_sandbox', $provider->is_sandbox ?? true))> Sandbox mode
            </label>
            <div>
                <x-input-label for="sort_order" value="Sort Order" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="block mt-1 w-full" :value="old('sort_order', $provider->sort_order ?? 0)" />
            </div>
        </div>

        <div class="flex gap-3">
            <x-primary-button>{{ $provider->exists ? 'Update' : 'Create' }}</x-primary-button>
            <a href="{{ route('admin.courier-providers.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
        </div>
    </form>
@endsection
