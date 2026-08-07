@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Order '.$order->order_number,
    ])

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('admin.orders.track', $order) }}" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Track Order</a>
        @can('billing.view')
            <a href="{{ route('admin.orders.invoice.print', $order) }}" target="_blank" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Print Invoice</a>
        @endcan
        @if ($order->shipments->isNotEmpty())
            @can('view', $order->shipments->first())
                <a href="{{ route('admin.shipping.label', $order->shipments->first()) }}" target="_blank" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Print Shipping Label</a>
            @endcan
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-4">Line Items</h2>
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Product</th><th>SKU</th><th>Qty</th><th>Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-2">
                                    <p>{{ $item->product_name }}</p>
                                    <x-storefront.variant-options :item="$item" class="text-gray-500 text-xs mt-1" />
                                    <x-order.item-offer-meta :item="$item" class="text-gray-500" />
                                </td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->formatted_total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4 border-t pt-4 text-sm">
                    <x-order.pricing-summary :record="$order" class="max-w-sm ml-auto [&_dt]:text-gray-600" />
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-4">Status History</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($order->statusHistories as $history)
                        <li class="flex justify-between border-b pb-2">
                            <span><x-order-status-badge :status="$history->status" class="mr-2" /> @if($history->note)<span class="text-gray-500">— {{ $history->note }}</span>@endif</span>
                            <span class="text-gray-500">{{ $history->created_at?->format('M j, H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">No status changes recorded.</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-4">Shipments</h2>
                @forelse ($order->shipments as $shipment)
                    <div class="border rounded-md p-4 mb-3 text-sm">
                        <div class="flex justify-between">
                            <strong>{{ $shipment->courier_name }}</strong>
                            <div class="space-x-2">
                                <a href="{{ route('admin.shipping.label', $shipment) }}" target="_blank" class="text-slate-700 hover:underline">Label</a>
                                <a href="{{ route('admin.shipping.show', $shipment) }}" class="text-slate-700 hover:underline">Manage</a>
                            </div>
                        </div>
                        <div class="text-gray-600 mt-1">
                            Tracking: {{ $shipment->tracking_number ?? '—' }}
                            @if ($shipment->awb_number)
                                · AWB: {{ $shipment->awb_number }}
                            @endif
                            · {{ $shipment->status?->value }}
                            @if ($shipment->booking_status)
                                · Booking: {{ str_replace('_', ' ', $shipment->booking_status->value) }}
                            @endif
                        </div>
                    </div>
                @empty
                    @can('create', App\Models\Shipping::class)
                        @if ($bookableCourierProviders->isNotEmpty())
                            <div class="border rounded-md p-4 mb-4">
                                <h3 class="font-medium mb-2">Book Shipment</h3>
                                <p class="text-sm text-gray-500 mb-3">Simulates courier booking and stores shipment details. No external API is called yet.</p>
                                <form method="POST" action="{{ route('admin.orders.shipping.book', $order) }}" class="flex flex-col sm:flex-row gap-3">
                                    @csrf
                                    <select name="courier_provider_id" required class="flex-1 rounded-md border-gray-300 shadow-sm">
                                        <option value="">Select courier</option>
                                        @foreach ($bookableCourierProviders as $provider)
                                            <option value="{{ $provider->id }}" @selected(old('courier_provider_id') == $provider->id)>
                                                {{ $provider->name }} ({{ $provider->mode_label }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm whitespace-nowrap">Book Shipment</button>
                                </form>
                                @error('courier_provider_id')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="border rounded-md p-4">
                            <h3 class="font-medium mb-2">Create Shipment Manually</h3>
                            <p class="text-sm text-gray-500 mb-3">Enter courier and tracking details yourself.</p>
                            <form method="POST" action="{{ route('admin.orders.shipping.store', $order) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @csrf
                                <input type="text" name="courier_name" placeholder="Courier" required class="rounded-md border-gray-300 shadow-sm">
                                <input type="text" name="tracking_number" placeholder="Tracking #" class="rounded-md border-gray-300 shadow-sm">
                                <select name="status" required class="rounded-md border-gray-300 shadow-sm">
                                    @foreach (\App\Enums\ShipmentStatus::cases() as $status)
                                        <option value="{{ $status->value }}">{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="md:col-span-3 px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Create Shipment</button>
                            </form>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No shipments yet.</p>
                    @endcan
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 text-sm space-y-2">
                <h2 class="text-lg font-medium mb-3">Customer</h2>
                <p><strong>{{ $order->customer_name }}</strong></p>
                <p>{{ $order->customer_email }}</p>
                <p>{{ $order->customer_phone ?? '—' }}</p>
            </div>

            @if (filled($order->coupon_code) || filled($order->coupon_id))
                @php
                    $couponDiscount = max(0, round(
                        (float) $order->discount_total - (float) ($order->offer_discount_total ?? 0),
                        2
                    ));
                    if ($couponDiscount <= 0 && (float) $order->discount_total > 0 && (float) ($order->offer_discount_total ?? 0) <= 0) {
                        $couponDiscount = (float) $order->discount_total;
                    }
                @endphp
                <div class="bg-white rounded-lg shadow p-6 text-sm space-y-2">
                    <h2 class="text-lg font-medium mb-3">Coupon</h2>
                    <p><strong>Coupon Code:</strong> {{ $order->trackedCoupon?->code ?? $order->coupon_code }}</p>
                    <p><strong>Influencer Name:</strong> {{ $order->influencer?->name ?? '—' }}</p>
                    <p><strong>Discount:</strong> <x-money :amount="$couponDiscount" /></p>
                    <p><strong>Commission Generated:</strong> <x-money :amount="$order->influencer_commission_amount ?? 0" /></p>
                </div>
            @endif

            @can('update', $order)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium mb-3">Update Status</h2>
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach (app(\App\Services\OrderWorkflowService::class)->active() as $status)
                                <option value="{{ $status->slug }}" @selected($order->status === $status->slug)>{{ $status->name }}</option>
                            @endforeach
                        </select>
                        <textarea name="note" rows="2" placeholder="Note (optional)" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        <x-primary-button class="w-full justify-center">Update Status</x-primary-button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium mb-3">Payment Status</h2>
                    <form method="POST" action="{{ route('admin.orders.update-payment-status', $order) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <select name="payment_status" class="w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach (\App\Enums\PaymentStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($order->payment_status === $status)>{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                            @endforeach
                        </select>
                        <x-primary-button class="w-full justify-center">Update Payment</x-primary-button>
                    </form>
                </div>
            @endcan

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-3">Billing</h2>
                @if ($order->invoice)
                    <p class="text-sm mb-2">Invoice: <a href="{{ route('admin.invoices.show', $order->invoice) }}" class="text-slate-700 hover:underline">{{ $order->invoice->invoice_number }}</a></p>
                    <p class="text-sm text-gray-600 mb-3">Status: {{ $order->invoice->status->value }}</p>
                    @can('billing.view')
                        <a href="{{ route('admin.orders.invoice.print', $order) }}" target="_blank" class="inline-flex px-4 py-2 border rounded-md text-sm hover:bg-gray-50">Print Invoice</a>
                    @endcan
                @else
                    @can('create', App\Models\Invoice::class)
                        <form method="POST" action="{{ route('admin.orders.invoice.generate', $order) }}">
                            @csrf
                            <x-primary-button>Generate Invoice</x-primary-button>
                        </form>
                    @endcan
                @endif
            </div>

            @can('cancel', $order)
                @if (!$order->is_cancelled)
                    <div class="bg-white rounded-lg shadow p-6">
                        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                            @csrf
                            <textarea name="note" rows="2" placeholder="Cancellation reason" class="w-full rounded-md border-gray-300 shadow-sm mb-3"></textarea>
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm">Cancel Order</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@endsection
