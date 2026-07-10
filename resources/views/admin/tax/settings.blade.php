@extends('layouts.admin')

@section('title', 'Tax & Charges')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Tax & Charges'])

    <p class="text-sm text-gray-500 mb-6">Configure VAT, GST, sales tax, and additional charges applied at checkout.</p>

    <form method="POST" action="{{ route('admin.tax.settings.update') }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PATCH')

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-5">
            <h3 class="font-semibold text-gray-900">Tax types</h3>
            <p class="text-sm text-gray-500">Configure each tax type and choose which one applies at checkout.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                    <label class="flex items-center gap-2 font-medium text-gray-900">
                        <input type="checkbox" name="vat_enabled" value="1" @checked(old('vat_enabled', $settings->vat_enabled))>
                        VAT
                    </label>
                    <div>
                        <label class="block text-sm text-gray-600">Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="vat_rate" value="{{ old('vat_rate', $settings->vat_rate) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                    <label class="flex items-center gap-2 font-medium text-gray-900">
                        <input type="checkbox" name="gst_enabled" value="1" @checked(old('gst_enabled', $settings->gst_enabled))>
                        GST
                    </label>
                    <div>
                        <label class="block text-sm text-gray-600">Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="gst_rate" value="{{ old('gst_rate', $settings->gst_rate) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                    <label class="flex items-center gap-2 font-medium text-gray-900">
                        <input type="checkbox" name="sales_tax_enabled" value="1" @checked(old('sales_tax_enabled', $settings->sales_tax_enabled))>
                        Sales Tax
                    </label>
                    <div>
                        <label class="block text-sm text-gray-600">Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="sales_tax_rate" value="{{ old('sales_tax_rate', $settings->sales_tax_rate) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>
            </div>

            <div class="max-w-md">
                <label class="block text-sm font-medium text-gray-700">Default tax at checkout</label>
                <select name="default_tax_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                    @foreach (\App\Enums\TaxType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('default_tax_type', $settings->default_tax_type?->value) === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
                @error('default_tax_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Service charge</h3>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="service_charge_enabled" value="1" @checked(old('service_charge_enabled', $settings->service_charge_enabled))>
                Enable service charge
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Calculation</label>
                    <select name="service_charge_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach (\App\Enums\ChargeCalculationType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('service_charge_type', $settings->service_charge_type?->value) === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Value</label>
                    <input type="number" step="0.01" min="0" name="service_charge_value" value="{{ old('service_charge_value', $settings->service_charge_value) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <p class="mt-1 text-xs text-gray-500">Percentage of discounted subtotal, or fixed amount per order.</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Handling charge</h3>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="handling_charge_enabled" value="1" @checked(old('handling_charge_enabled', $settings->handling_charge_enabled))>
                Enable handling charge
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Calculation</label>
                    <select name="handling_charge_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach (\App\Enums\ChargeCalculationType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('handling_charge_type', $settings->handling_charge_type?->value) === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Value</label>
                    <input type="number" step="0.01" min="0" name="handling_charge_value" value="{{ old('handling_charge_value', $settings->handling_charge_value) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <p class="mt-1 text-xs text-gray-500">Percentage of discounted subtotal, or fixed amount per order.</p>
                </div>
            </div>
        </div>

        @can('update', $settings)
            <x-primary-button>Save tax & charge settings</x-primary-button>
        @endcan
    </form>
@endsection
