@extends('layouts.storefront')

@section('title', 'Payment — '.config('app.name'))

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-3xl font-bold mb-2">Complete your payment</h1>
        <p class="text-slate-600 mb-8">Order <strong>{{ $order->order_number }}</strong> — Total: <x-money :amount="$order->grand_total" /></p>

        @if ($order->payment_method->value === 'bank_transfer')
            <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-lg">Bank transfer details</h2>
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">Bank</dt><dd class="font-medium">{{ $bankDetails['bank_name'] }}</dd></div>
                    <div><dt class="text-slate-500">Account name</dt><dd class="font-medium">{{ $bankDetails['account_name'] }}</dd></div>
                    <div><dt class="text-slate-500">Account number</dt><dd class="font-medium">{{ $bankDetails['account_number'] }}</dd></div>
                    <div><dt class="text-slate-500">IBAN</dt><dd class="font-medium">{{ $bankDetails['iban'] }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Reference</dt><dd class="font-medium">{{ $order->order_number }}</dd></div>
                </dl>
                <p class="text-sm text-slate-600">{{ $bankDetails['instructions'] }}</p>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-sm text-amber-900">
                Online payment for {{ str($order->payment_method->name)->headline() }} is not fully configured yet.
                Your order has been recorded and our team will contact you if needed.
            </div>
        @endif

        <a href="{{ route('shop.checkout.success', $order) }}" class="inline-flex mt-8 text-sm font-medium text-slate-900 underline">
            Back to order confirmation
        </a>
    </div>
@endsection
