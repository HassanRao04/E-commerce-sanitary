@extends('layouts.admin')

@section('title', 'Invoices')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Invoices'])

    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Invoice #, customer..."
                class="rounded-md border-gray-300 shadow-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All statuses</option>
                @foreach (\App\Enums\InvoiceStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="overdue" value="1" @checked(request('overdue'))> Overdue only
            </label>
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Invoice</th>
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Total</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($invoices as $invoice)
                    <tr class="{{ $invoice->is_overdue ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3 font-medium">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3">{{ $invoice->order?->order_number }}</td>
                        <td class="px-4 py-3">{{ $invoice->billing_name }}</td>
                        <td class="px-4 py-3">{{ $invoice->formatted_total }}</td>
                        <td class="px-4 py-3">{{ $invoice->status->value }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-slate-700 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No invoices found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($invoices->hasPages())
            <div class="px-4 py-3 border-t">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection
