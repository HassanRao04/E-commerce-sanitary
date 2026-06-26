@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Products',
        'permission' => 'products.create',
        'actionRoute' => route('admin.products.create'),
        'actionLabel' => 'Add Product',
    ])

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or SKU..."
                class="rounded-md border-gray-300 shadow-sm text-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ProductStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
            <select name="brand_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) request('brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">SKU</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Price</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Stock</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Variants</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($products as $product)
                    @php
                        $variant = $product->defaultVariant;
                        $totalStock = $product->product_type === 'variable'
                            ? $product->variants->sum('stock_quantity')
                            : ($variant?->stock_quantity ?? 0);
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $product->primary_image_url }}" alt="" class="h-10 w-10 rounded object-cover bg-gray-100">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $product->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->brand?->name ?? 'No brand' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-600">{{ $product->base_sku }}</td>
                        <td class="px-4 py-3 capitalize text-gray-600">{{ $product->product_type ?? 'simple' }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ config('shop.currency_symbol') }} {{ number_format($variant?->price ?? 0, 2) }}
                            @if ($variant?->sale_price)
                                <span class="text-xs text-green-600">Sale {{ config('shop.currency_symbol') }}{{ number_format($variant->sale_price, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $totalStock <= config('shop.low_stock_threshold') ? 'text-amber-600 font-medium' : 'text-gray-600' }}">
                                {{ $totalStock }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $product->variants_count }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs bg-gray-100 capitalize">{{ $product->status->value }}</span></td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('update', $product)
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800">Manage</a>
                            @endcan
                            @can('delete', $product)
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Delete this product?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($products->hasPages())
            <div class="px-4 py-3 border-t">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
