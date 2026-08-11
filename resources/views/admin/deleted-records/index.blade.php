@extends('layouts.admin')

@section('title', 'Deleted Records')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Deleted Records'])

    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Restoring a record brings it back into normal admin queries. Files removed during deletion (product images, category banners, profile photos) are not automatically recovered.
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search name, code, ID..."
                class="rounded-md border-gray-300 shadow-sm text-sm md:col-span-2"
            >
            <select name="type" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All entity types</option>
                @foreach ($entityTypes as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="deleted_by" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">Deleted by anyone</option>
                @foreach ($staffUsers as $staffUser)
                    <option value="{{ $staffUser->id }}" @selected((string) request('deleted_by') === (string) $staffUser->id)>
                        {{ $staffUser->full_name }}
                    </option>
                @endforeach
            </select>
            <input
                type="date"
                name="deleted_from"
                value="{{ request('deleted_from') }}"
                class="rounded-md border-gray-300 shadow-sm text-sm"
                title="Deleted from"
            >
            <input
                type="date"
                name="deleted_to"
                value="{{ request('deleted_to') }}"
                class="rounded-md border-gray-300 shadow-sm text-sm"
                title="Deleted to"
            >
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm md:col-span-6 md:max-w-[8rem]">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Entity</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Record</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">ID</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Deleted By</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Deleted At</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($records as $record)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $record->typeLabel }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $record->identifier }}</div>
                            @if ($record->subtitle)
                                <div class="text-xs text-gray-500">{{ $record->subtitle }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">#{{ $record->id }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @if ($record->deletedBy)
                                {{ $record->deletedBy->full_name }}
                            @else
                                <span class="text-gray-400">Unknown</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                            <x-admin.datetime :at="$record->deletedAt" />
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                {{ $record->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('records.restore')
                                <form
                                    method="POST"
                                    action="{{ route('admin.deleted-records.restore', ['type' => $record->type, 'id' => $record->id]) }}"
                                    class="inline"
                                    onsubmit="return confirm('Restore this {{ strtolower($record->typeLabel) }}?');"
                                >
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        Restore
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400">—</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No deleted records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($records->hasPages())
            <div class="px-4 py-3 border-t">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
