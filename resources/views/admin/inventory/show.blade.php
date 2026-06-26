@extends('layouts.admin')

@section('title', 'Manage Stock')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Manage Stock'])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium mb-4">{{ $item->productVariant?->product?->name }}</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">SKU</dt><dd class="font-medium">{{ $item->productVariant?->sku }}</dd></div>
                <div><dt class="text-gray-500">Warehouse</dt><dd class="font-medium">{{ $item->warehouse?->name }}</dd></div>
                <div><dt class="text-gray-500">On Hand</dt><dd class="font-medium">{{ $item->quantity_on_hand }}</dd></div>
                <div><dt class="text-gray-500">Available</dt><dd class="font-medium">{{ $item->available_quantity }}</dd></div>
            </dl>

            <h3 class="text-md font-medium mt-8 mb-3">Recent Movements</h3>
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead><tr class="text-left text-gray-500"><th class="py-2">Type</th><th>Qty</th><th>Balance</th><th>When</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="py-2">{{ $movement->movement_type->value }}</td>
                            <td>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                            <td>{{ $movement->balance_after }}</td>
                            <td>{{ $movement->created_at?->format('M j, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-gray-500">No movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('update', $item)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-4">Adjust Stock</h2>
                <form method="POST" action="{{ route('admin.inventory.adjust', $item) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <div>
                        <x-input-label for="movement_type" value="Movement Type" />
                        <select id="movement_type" name="movement_type" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                            @foreach ($movementTypes as $type)
                                <option value="{{ $type->value }}">{{ str_replace('_', ' ', ucfirst($type->value)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="quantity" value="Quantity" />
                        <x-text-input id="quantity" name="quantity" type="number" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="notes" value="Notes" />
                        <textarea id="notes" name="notes" rows="2" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
                    </div>
                    <x-primary-button>Apply Adjustment</x-primary-button>
                </form>
            </div>
        @endcan
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.inventory.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back to inventory</a>
    </div>
@endsection
