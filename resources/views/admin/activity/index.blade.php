@extends('layouts.admin')

@section('title', 'Activity Log')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Activity Log'])

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search description, action, IP..."
                class="rounded-md border-gray-300 shadow-sm text-sm md:col-span-2"
            >
            <select name="action" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All user actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action->value }}" @selected(request('action') === $action->value)>
                        {{ $action->label() }}
                    </option>
                @endforeach
            </select>
            <select name="subject" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All subjects</option>
                @foreach ($staffUsers as $staffUser)
                    <option value="{{ $staffUser->id }}" @selected((string) request('subject') === (string) $staffUser->id)>
                        {{ $staffUser->full_name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">User</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Action</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Description</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">IP Address</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Browser</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3">
                            @if ($log->user)
                                @can('view', $log->user)
                                    <a href="{{ route('admin.users.show', $log->user) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ $log->user->full_name }}
                                    </a>
                                @else
                                    <span class="font-medium text-gray-900">{{ $log->user->full_name }}</span>
                                @endcan
                            @else
                                <span class="text-gray-500">System</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                {{ $log->action_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $log->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $log->browser ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No activity logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($logs->hasPages())
            <div class="px-4 py-3 border-t">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
