@extends('layouts.admin')

@section('title', 'Track Order '.$order->order_number)

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Track Order '.$order->order_number,
    ])

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Current Status</p>
                        <div class="mt-1"><x-order-status-badge :status="$order->status" /></div>
                    </div>
                    @if ($order->tracking_token)
                        <div class="text-right text-sm">
                            <p class="text-gray-500">Tracking reference</p>
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $order->tracking_token }}</code>
                        </div>
                    @endif
                </div>

                <h2 class="text-lg font-medium mb-4">Order Timeline</h2>
                <ol class="relative border-l border-gray-200 ml-3 space-y-6">
                    @foreach ($order->statusHistories->sortByDesc('created_at') as $history)
                        <li class="ml-6">
                            <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-indigo-600"></span>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-order-status-badge :status="$history->status" />
                                <span class="text-xs text-gray-500">{{ $history->created_at?->format('M j, Y H:i') }}</span>
                            </div>
                            @if ($history->note)
                                <p class="text-sm text-gray-600 mt-1">{{ $history->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            @foreach ($order->shipments as $shipment)
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-medium">{{ $shipment->courier_name }}</h2>
                            <p class="text-sm text-gray-500">Tracking: {{ $shipment->tracking_number ?? 'Not assigned' }}</p>
                        </div>
                        <div class="flex gap-2">
                            @can('view', $shipment)
                                <a href="{{ route('admin.shipping.label', $shipment) }}" target="_blank" class="px-3 py-2 text-sm border rounded-md hover:bg-gray-50">Print Label</a>
                                <a href="{{ route('admin.shipping.show', $shipment) }}" class="px-3 py-2 text-sm bg-slate-900 text-white rounded-md">Manage</a>
                            @endcan
                        </div>
                    </div>

                    @if ($shipment->tracking_url)
                        <p class="text-sm mb-4"><a href="{{ $shipment->tracking_url }}" target="_blank" class="text-indigo-600 hover:underline">Open courier tracking →</a></p>
                    @endif

                    <ul class="space-y-3 text-sm">
                        @forelse ($shipment->trackingEvents as $event)
                            <li class="border-b pb-3">
                                <strong>{{ $event->status }}</strong>
                                @if ($event->location) <span class="text-gray-500">· {{ $event->location }}</span> @endif
                                <div class="text-gray-500">{{ $event->event_at?->format('M j, Y H:i') }}</div>
                                @if ($event->description)
                                    <div class="text-gray-600">{{ $event->description }}</div>
                                @endif
                            </li>
                        @empty
                            <li class="text-gray-500">No shipment events recorded yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 text-sm space-y-2">
                <h2 class="text-lg font-medium mb-3">Customer</h2>
                <p><strong>{{ $order->customer_name }}</strong></p>
                <p>{{ $order->customer_email }}</p>
                <p>{{ $order->customer_phone ?? '—' }}</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6 text-sm">
                <h2 class="text-lg font-medium mb-3">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.orders.show', $order) }}" class="block w-full text-center px-4 py-2 border rounded-md hover:bg-gray-50">Order Details</a>
                    @can('billing.view')
                        <a href="{{ route('admin.orders.invoice.print', $order) }}" target="_blank" class="block w-full text-center px-4 py-2 border rounded-md hover:bg-gray-50">Print Invoice</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
