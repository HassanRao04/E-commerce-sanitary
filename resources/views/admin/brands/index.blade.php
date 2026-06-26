@extends('layouts.admin')

@section('title', 'Brands')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Brands',
        'permission' => 'brands.manage',
        'actionRoute' => route('admin.brands.create'),
        'actionLabel' => 'Add Brand',
    ])

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Slug</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Products</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Active</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($brands as $brand)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $brand->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $brand->slug }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $brand->products_count }}</td>
                        <td class="px-4 py-3">{{ $brand->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('update', $brand)
                                <a href="{{ route('admin.brands.edit', $brand) }}" class="text-slate-700 hover:underline">Edit</a>
                            @endcan
                            @can('delete', $brand)
                                <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" class="inline" onsubmit="return confirm('Delete this brand?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No brands found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($brands->hasPages())
            <div class="px-4 py-3 border-t">{{ $brands->links() }}</div>
        @endif
    </div>
@endsection
