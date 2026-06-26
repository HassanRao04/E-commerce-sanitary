@extends('layouts.admin')

@section('title', 'Payments')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Payments'])

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Transaction, order..."
                class="rounded-md border-gray-300 shadow-sm text-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Enums\PaymentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
                @endforeach
            </select>
            <select name="gateway" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All gateways</option>
                @foreach (\App\Enums\PaymentMethod::cases() as $method)
                    <option value="{{ $method->value }}" @selected(request('gateway') === $method->value)>{{ ucfirst($method->value) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Transaction</th>
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">Gateway</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $payment->transaction_id ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $payment->order?->order_number }}</td>
                        <td class="px-4 py-3">{{ $payment->gateway?->value }}</td>
                        <td class="px-4 py-3">{{ $payment->formatted_amount }}</td>
                        <td class="px-4 py-3">{{ $payment->status->value }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-indigo-600 hover:text-indigo-800">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No payments recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($payments->hasPages())
            <div class="px-4 py-3 border-t">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection
