@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Customers'])

    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email, company..."
                class="rounded-md border-gray-300 shadow-sm">
            <select name="customer_type" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All types</option>
                @foreach (\App\Enums\CustomerType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(request('customer_type') === $type->value)>{{ ucfirst($type->value) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Orders</th>
                    <th class="px-4 py-3 text-left">Lifetime Spend</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $customer->user?->name ?? $customer->display_name }}</div>
                            <div class="text-gray-500 text-xs">{{ $customer->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $customer->customer_type->value }}</td>
                        <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-3">{{ $customer->formatted_lifetime_spend }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('view', $customer)
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-slate-700 hover:underline">View</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($customers->hasPages())
            <div class="px-4 py-3 border-t">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
