@extends('layouts.admin')

@section('title', 'Courier Providers')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Courier Providers',
        'permission' => 'shipping.manage',
        'actionRoute' => route('admin.courier-providers.create'),
        'actionLabel' => 'Add Courier',
    ])

    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, slug, or city..."
                class="flex-1 rounded-md border-gray-300 shadow-sm">
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm">Search</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Courier</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Mode</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Pickup City</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Shipments</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($providers as $provider)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($provider->logo_url)
                                    <img src="{{ $provider->logo_url }}" alt="" class="h-8 w-8 rounded object-contain border border-gray-200 bg-white">
                                @else
                                    <div class="h-8 w-8 rounded bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-500">
                                        {{ str($provider->name)->substr(0, 2)->upper() }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium">{{ $provider->name }}</div>
                                    <div class="text-gray-500 text-xs">{{ $provider->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $provider->is_sandbox ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $provider->mode_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $provider->pickup_city ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $provider->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $provider->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $provider->shipments_count }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('update', $provider)
                                <a href="{{ route('admin.courier-providers.edit', $provider) }}" class="text-slate-700 hover:underline">Edit</a>
                            @endcan
                            @can('delete', $provider)
                                @if ($provider->slug !== 'manual')
                                    <form action="{{ route('admin.courier-providers.destroy', $provider) }}" method="POST" class="inline" onsubmit="return confirm('Delete this courier provider?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No courier providers found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($providers->hasPages())
            <div class="px-4 py-3 border-t">{{ $providers->links() }}</div>
        @endif
    </div>
@endsection
