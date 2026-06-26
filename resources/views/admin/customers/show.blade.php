@extends('layouts.admin')

@section('title', $customer->display_name)

@section('content')
    @include('admin.partials.page-header', ['title' => $customer->display_name])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6 text-sm space-y-3">
            <h2 class="text-lg font-medium mb-3">Profile</h2>
            <p><strong>Name:</strong> {{ $customer->user?->name }}</p>
            <p><strong>Email:</strong> {{ $customer->user?->email }}</p>
            <p><strong>Phone:</strong> {{ $customer->user?->phone ?? '—' }}</p>
            <p><strong>Company:</strong> {{ $customer->company_name ?? '—' }}</p>
            <p><strong>Tax #:</strong> {{ $customer->tax_number ?? '—' }}</p>
            <p><strong>Type:</strong> {{ $customer->customer_type->value }}</p>
            <p><strong>Credit Limit:</strong> {{ config('shop.currency_symbol') }} {{ number_format($customer->credit_limit, 2) }}</p>
            <p><strong>Lifetime Spend:</strong> {{ $customer->formatted_lifetime_spend }}</p>
            <p><strong>Orders:</strong> {{ $customer->orders_count }}</p>
            @if ($customer->notes)
                <p><strong>Notes:</strong> {{ $customer->notes }}</p>
            @endif
        </div>
        <div class="space-y-4">
            @can('update', $customer)
                <a href="{{ route('admin.customers.edit', $customer) }}" class="block text-center px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Edit Customer</a>
            @endcan
            <a href="{{ route('admin.customers.index') }}" class="block text-center px-4 py-2 text-gray-600 text-sm">← Back to customers</a>
        </div>
    </div>
@endsection
