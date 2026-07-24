@extends('layouts.admin')

@section('title', $invoice->invoice_number)

@section('content')
    @include('admin.partials.page-header', ['title' => 'Invoice '.$invoice->invoice_number])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <dl class="grid grid-cols-2 gap-4 text-sm mb-6">
                <div><dt class="text-gray-500">Bill To</dt><dd class="font-medium">{{ $invoice->billing_name }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd>{{ $invoice->billing_email }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Address</dt><dd>{{ $invoice->billing_address ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Order</dt><dd><a href="{{ route('admin.orders.show', $invoice->order) }}" class="text-slate-700 hover:underline">{{ $invoice->order?->order_number }}</a></dd></div>
                <div><dt class="text-gray-500">Status</dt><dd>{{ $invoice->status->value }}</dd></div>
            </dl>

            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead><tr class="text-left text-gray-500"><th class="py-2">Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td class="py-2">
                                <p>{{ $item->product_name }} {{ $item->variant_name }}</p>
                                <x-order.item-offer-meta :item="$item" class="text-gray-500" />
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ config('shop.currency_symbol') }} {{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ config('shop.currency_symbol') }} {{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 text-sm">
                <x-order.pricing-summary
                    :record="$invoice"
                    :show-charges="true"
                    class="max-w-sm ml-auto [&_dt]:text-gray-600"
                />
            </div>
        </div>

        @can('update', $invoice)
            <div class="bg-white rounded-lg shadow p-6 space-y-3">
                <h2 class="text-lg font-medium mb-3">Actions</h2>
                @if ($invoice->status === \App\Enums\InvoiceStatus::Draft)
                    <form method="POST" action="{{ route('admin.invoices.issue', $invoice) }}">@csrf @method('PATCH')<x-primary-button class="w-full justify-center">Issue Invoice</x-primary-button></form>
                @endif
                @if (in_array($invoice->status, [\App\Enums\InvoiceStatus::Draft, \App\Enums\InvoiceStatus::Issued, \App\Enums\InvoiceStatus::Overdue]))
                    <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}">@csrf @method('PATCH')<x-secondary-button class="w-full justify-center">Mark Paid</x-secondary-button></form>
                @endif
                @if ($invoice->status !== \App\Enums\InvoiceStatus::Void)
                    <form method="POST" action="{{ route('admin.invoices.void', $invoice) }}" onsubmit="return confirm('Void this invoice?')">@csrf @method('PATCH')<button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm">Void</button></form>
                @endif
            </div>
        @endcan
    </div>
@endsection
