@extends('layouts.admin')

@section('title', 'Inventory')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Inventory'])

    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search SKU or product..."
                class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <select name="warehouse_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">All warehouses</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) request('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock'))> Low stock only
            </label>
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">SKU</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Warehouse</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">On Hand</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Reserved</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Available</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($items as $item)
                    <tr class="{{ $item->is_low_stock ? 'bg-amber-50' : '' }}">
                        <td class="px-4 py-3 font-medium">{{ $item->productVariant?->product?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->productVariant?->sku }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->warehouse?->name }}</td>
                        <td class="px-4 py-3">{{ $item->quantity_on_hand }}</td>
                        <td class="px-4 py-3">{{ $item->quantity_reserved }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->available_quantity }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('view', $item)
                                <a href="{{ route('admin.inventory.show', $item) }}" class="text-slate-700 hover:underline">Manage</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No inventory records. Seed demo products or create products first.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($items->hasPages())
            <div class="px-4 py-3 border-t">{{ $items->links() }}</div>
        @endif
    </div>
@endsection
