@extends('layouts.admin')

@section('title', 'Checkout Rules')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Checkout Rules'])

    <p class="text-sm text-gray-500 mb-6">
        Central checkout rules engine. Shipping, tax, and coupon definitions are managed in their respective ERP modules;
        totals at checkout are calculated from those settings plus the rules below.
    </p>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60">
            <h3 class="font-semibold text-gray-900">Shipping rules</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Free shipping</dt>
                    <dd class="text-gray-900 text-right">
                        @if ($rules['shipping']['free_shipping_enabled'] && $rules['shipping']['free_shipping_threshold'] > 0)
                            Above {{ config('shop.currency_symbol') }}{{ number_format($rules['shipping']['free_shipping_threshold'], 0) }}
                        @else
                            Disabled
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Default method</dt>
                    <dd class="text-gray-900 capitalize">{{ str_replace('_', ' ', $rules['shipping']['default_method'] ?? 'flat') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Flat rate</dt>
                    <dd class="text-gray-900">
                        @if ($rules['shipping']['flat_rate_enabled'])
                            {{ config('shop.currency_symbol') }}{{ number_format($rules['shipping']['flat_rate_amount'], 2) }}
                        @else
                            Off
                        @endif
                    </dd>
                </div>
            </dl>
            @can('shipping.view')
                <a href="{{ route('admin.shipping.settings.edit') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:underline">Edit shipping settings</a>
            @endcan
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60">
            <h3 class="font-semibold text-gray-900">Tax rules</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Active tax</dt>
                    <dd class="text-gray-900">{{ $rules['tax']['tax_label'] ?: 'None' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Rate</dt>
                    <dd class="text-gray-900">{{ number_format($rules['tax']['tax_rate'], 2) }}%</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Service charge</dt>
                    <dd class="text-gray-900">{{ $rules['tax']['service_charge_enabled'] ? 'Enabled' : 'Off' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Handling charge</dt>
                    <dd class="text-gray-900">{{ $rules['tax']['handling_charge_enabled'] ? 'Enabled' : 'Off' }}</dd>
                </div>
            </dl>
            @can('tax.view')
                <a href="{{ route('admin.tax.settings.edit') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:underline">Edit tax &amp; charges</a>
            @endcan
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60">
            <h3 class="font-semibold text-gray-900">Coupon rules</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Storefront coupons</dt>
                    <dd class="text-gray-900">{{ $rules['coupons']['enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Per-coupon minimums</dt>
                    <dd class="text-gray-900">Set on each coupon</dd>
                </div>
            </dl>
            @can('coupons.view')
                <a href="{{ route('admin.coupons.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:underline">Manage coupons</a>
            @endcan
        </div>
    </div>

    <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700 mb-6">
        <strong>Checkout formula:</strong>
        Subtotal + Shipping + Service charge + Handling charge + Tax − Discount = Grand Total
    </div>

    <form method="POST" action="{{ route('admin.checkout.rules.update') }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PATCH')

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-5">
            <h3 class="font-semibold text-gray-900">Order requirements</h3>

            <label class="flex items-center gap-2 font-medium text-gray-900">
                <input type="checkbox" name="minimum_order_enabled" value="1" @checked(old('minimum_order_enabled', $settings->minimum_order_enabled))>
                Enforce minimum order amount
            </label>

            <div>
                <label for="minimum_order_amount" class="block text-sm text-gray-600">Minimum order amount ({{ config('shop.currency_symbol') }})</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="minimum_order_amount"
                    name="minimum_order_amount"
                    value="{{ old('minimum_order_amount', $settings->minimum_order_amount) }}"
                    class="mt-1 w-full max-w-xs rounded-md border-gray-300 shadow-sm text-sm"
                >
                @error('minimum_order_amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 font-medium text-gray-900">
                <input type="checkbox" name="coupons_enabled" value="1" @checked(old('coupons_enabled', $settings->coupons_enabled))>
                Allow coupons at checkout
            </label>
        </div>

        @can('checkout_rules.manage')
            <button type="submit" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Save checkout rules
            </button>
        @endcan
    </form>
@endsection
