@extends('layouts.admin')

@section('title', 'Payment')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Payment Details'])

    <div class="max-w-3xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-gray-500">Transaction ID</dt><dd class="font-medium">{{ $payment->transaction_id ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Gateway</dt><dd>{{ $payment->gateway?->value }}</dd></div>
            <div><dt class="text-gray-500">Order</dt><dd><a href="{{ route('admin.orders.show', $payment->order) }}" class="text-indigo-600">{{ $payment->order?->order_number }}</a></dd></div>
            <div><dt class="text-gray-500">Amount</dt><dd class="font-semibold">{{ $payment->formatted_amount }}</dd></div>
            <div><dt class="text-gray-500">Status</dt><dd>{{ $payment->status->value }}</dd></div>
            <div><dt class="text-gray-500">Paid At</dt><dd>{{ $payment->paid_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
        </dl>
    </div>
@endsection
