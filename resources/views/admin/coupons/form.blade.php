@extends('layouts.admin')

@section('title', $coupon->exists ? 'Edit Coupon' : 'Create Coupon')

@section('content')
    @include('admin.partials.page-header', ['title' => $coupon->exists ? 'Edit Coupon' : 'Create Coupon'])

    <form method="POST"
          action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
          class="max-w-2xl space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
        @csrf
        @if ($coupon->exists)
            @method('PUT')
        @endif

        <div>
            <x-input-label for="code" value="Coupon code" />
            <x-text-input id="code" name="code" class="mt-1 block w-full font-mono uppercase" :value="old('code', $coupon->code)" required />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="type" value="Discount type" />
                <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                    @foreach (\App\Enums\CouponType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $coupon->type?->value) === $type->value)>
                            {{ ucfirst($type->value) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="value" value="Value (% or fixed amount)" />
                <x-text-input id="value" name="value" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('value', $coupon->value)" required />
                <x-input-error :messages="$errors->get('value')" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="min_order_amount" value="Minimum order amount" />
                <x-text-input id="min_order_amount" name="min_order_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('min_order_amount', $coupon->min_order_amount)" />
                <x-input-error :messages="$errors->get('min_order_amount')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="max_uses" value="Maximum uses (optional)" />
                <x-text-input id="max_uses" name="max_uses" type="number" min="1" class="mt-1 block w-full" :value="old('max_uses', $coupon->max_uses)" />
                <x-input-error :messages="$errors->get('max_uses')" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="starts_at" value="Starts at" />
                <x-text-input id="starts_at" name="starts_at" type="datetime-local" class="mt-1 block w-full"
                    :value="old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i'))" />
                <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="expires_at" value="Expires at" />
                <x-text-input id="expires_at" name="expires_at" type="datetime-local" class="mt-1 block w-full"
                    :value="old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i'))" />
                <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true))>
            Active
        </label>

        @if ($coupon->exists)
            <p class="text-sm text-gray-500">Used {{ number_format($coupon->used_count) }} time(s).</p>
        @endif

        <div class="flex gap-3">
            <x-primary-button>{{ $coupon->exists ? 'Update coupon' : 'Create coupon' }}</x-primary-button>
            <a href="{{ route('admin.coupons.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
        </div>
    </form>
@endsection
