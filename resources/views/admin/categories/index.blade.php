@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Categories',
        'permission' => 'categories.manage',
        'actionRoute' => route('admin.categories.create'),
        'actionLabel' => 'Add Category',
    ])

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Image</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Slug</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Parent</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Products</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Active</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">
                            @if ($category->image_url)
                                <img src="{{ $category->image_url }}" alt="" class="h-10 w-10 rounded object-cover ring-1 ring-gray-200">
                            @else
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $category->slug }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $category->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $category->products_count }}</td>
                        <td class="px-4 py-3">{{ $category->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('update', $category)
                                <a href="{{ route('admin.categories.edit', $category) }}" class="text-slate-700 hover:underline">Edit</a>
                            @endcan
                            @can('delete', $category)
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($categories->hasPages())
            <div class="px-4 py-3 border-t">{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
