@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Site Settings'])

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf @method('PATCH')

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">General</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700">Site Name</label>
                <input type="text" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                    <input type="email" name="email" value="{{ old('email', $settings->email) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="address" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('address', $settings->address) }}</textarea>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Commerce</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Currency</label>
                    <input type="text" name="currency" value="{{ old('currency', $settings->currency) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Flat Shipping Rate</label>
                    <input type="number" step="0.01" name="shipping_flat_rate" value="{{ old('shipping_flat_rate', $settings->shipping_flat_rate) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Legacy field. Configure shipping in <a href="{{ route('admin.shipping.settings.edit') }}" class="text-indigo-600 hover:underline">Shipping Settings</a>.</p>
                </div>
            </div>
            <p class="text-sm text-gray-500">Tax and additional charges are managed in <a href="{{ route('admin.tax.settings.edit') }}" class="text-indigo-600 hover:underline">Tax &amp; Charges</a>.</p>
        </div>

        @can('update', $settings)
            <x-primary-button>Save Settings</x-primary-button>
        @endcan
    </form>
@endsection
