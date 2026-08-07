@extends('layouts.admin')

@section('title', 'Shipment')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Shipment — '.$shipment->order?->order_number])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6 text-sm">
                <dl class="grid grid-cols-2 gap-4 mb-4">
                    <div><dt class="text-gray-500">Order</dt><dd><a href="{{ route('admin.orders.show', $shipment->order) }}" class="text-slate-700 hover:underline">{{ $shipment->order?->order_number }}</a></dd></div>
                    <div><dt class="text-gray-500">Status</dt><dd>{{ str($shipment->status?->value)->headline() }}</dd></div>
                    <div><dt class="text-gray-500">Courier</dt><dd>{{ $shipment->courier_name }}</dd></div>
                    <div><dt class="text-gray-500">Tracking</dt><dd>{{ $shipment->tracking_number ?? '—' }}</dd></div>
                </dl>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.shipping.label', $shipment) }}" target="_blank" class="inline-flex px-4 py-2 border rounded-md text-sm hover:bg-gray-50">Print Shipping Label</a>
                    @if ($shipment->courierProvider?->slug === 'tcs' && $shipment->tracking_number)
                        <a href="{{ route('admin.shipping.courier-label', $shipment) }}" class="inline-flex px-4 py-2 border rounded-md text-sm hover:bg-gray-50">Download Courier Label</a>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-3">Tracking Timeline</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($shipment->trackingEvents as $event)
                        <li class="border-b pb-2">
                            <strong>{{ $event->status }}</strong>
                            @if ($event->location) — {{ $event->location }} @endif
                            <div class="text-gray-500">{{ $event->event_at?->format('M j, Y H:i') }} · {{ $event->summary }}</div>
                        </li>
                    @empty
                        <li class="text-gray-500">No tracking events yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        @can('update', $shipment)
            <div class="space-y-6">
                @if ($shipment->courierProvider?->slug === 'tcs' && $shipment->tracking_number)
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium mb-3">Courier API</h2>
                        <form method="POST" action="{{ route('admin.shipping.sync-tracking', $shipment) }}">
                            @csrf
                            <x-primary-button class="w-full justify-center">Sync Tracking from TCS</x-primary-button>
                        </form>
                    </div>
                @endif
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium mb-3">Update Shipment</h2>
                    <form method="POST" action="{{ route('admin.shipping.update', $shipment) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <input type="text" name="courier_name" value="{{ $shipment->courier_name }}" required class="w-full rounded-md border-gray-300 shadow-sm">
                        <input type="text" name="tracking_number" value="{{ $shipment->tracking_number }}" class="w-full rounded-md border-gray-300 shadow-sm">
                        <select name="status" required class="w-full rounded-md border-gray-300 shadow-sm">
                            @foreach (\App\Enums\ShipmentStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($shipment->status === $status)>{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                            @endforeach
                        </select>
                        <x-primary-button class="w-full justify-center">Save</x-primary-button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium mb-3">Add Tracking Event</h2>
                    <form method="POST" action="{{ route('admin.shipping.events.store', $shipment) }}" class="space-y-3">
                        @csrf
                        <input type="text" name="status" placeholder="Status" required class="w-full rounded-md border-gray-300 shadow-sm">
                        <input type="text" name="location" placeholder="Location" class="w-full rounded-md border-gray-300 shadow-sm">
                        <textarea name="description" rows="2" placeholder="Description" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        <input type="datetime-local" name="event_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full rounded-md border-gray-300 shadow-sm">
                        <x-primary-button class="w-full justify-center">Add Event</x-primary-button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection
