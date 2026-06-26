@extends('layouts.admin')

@section('title', 'Edit Customer')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Edit Customer'])

    <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="bg-white rounded-lg shadow p-6 space-y-6 max-w-2xl">
        @csrf @method('PATCH')

        <div>
            <x-input-label for="company_name" value="Company Name" />
            <x-text-input id="company_name" name="company_name" class="block mt-1 w-full" :value="old('company_name', $customer->company_name)" />
        </div>
        <div>
            <x-input-label for="tax_number" value="Tax Number" />
            <x-text-input id="tax_number" name="tax_number" class="block mt-1 w-full" :value="old('tax_number', $customer->tax_number)" />
        </div>
        <div>
            <x-input-label for="customer_type" value="Customer Type" />
            <select id="customer_type" name="customer_type" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                @foreach (\App\Enums\CustomerType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('customer_type', $customer->customer_type->value) === $type->value)>{{ ucfirst($type->value) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="credit_limit" value="Credit Limit" />
            <x-text-input id="credit_limit" name="credit_limit" type="number" step="0.01" class="block mt-1 w-full" :value="old('credit_limit', $customer->credit_limit)" />
        </div>
        <div>
            <x-input-label for="notes" value="Notes" />
            <textarea id="notes" name="notes" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $customer->notes) }}</textarea>
        </div>

        <div class="flex gap-3">
            <x-primary-button>Save Changes</x-primary-button>
            <a href="{{ route('admin.customers.show', $customer) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
        </div>
    </form>
@endsection
