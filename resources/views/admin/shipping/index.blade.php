@extends('layouts.admin')

@section('title', 'Shipping')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Shipping'])

    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Tracking #, order..."
                class="rounded-md border-gray-300 shadow-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ShipmentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">Courier</th>
                    <th class="px-4 py-3 text-left">Tracking</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($shipments as $shipment)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $shipment->order?->order_number }}</td>
                        <td class="px-4 py-3">{{ $shipment->courier_name }}</td>
                        <td class="px-4 py-3">{{ $shipment->tracking_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $shipment->status?->value }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.shipping.show', $shipment) }}" class="text-slate-700 hover:underline">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No shipments found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($shipments->hasPages())
            <div class="px-4 py-3 border-t">{{ $shipments->links() }}</div>
        @endif
    </div>
@endsection
