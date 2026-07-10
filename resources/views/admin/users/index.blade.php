@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Staff Users',
        'permission' => 'users.create',
        'actionRoute' => route('admin.users.create'),
        'actionLabel' => 'Add User',
    ])

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4">
            <input
                type="text"
                name="name"
                value="{{ request('name') }}"
                placeholder="Search by name"
                class="rounded-md border-gray-300 shadow-sm text-sm"
            >
            <input
                type="text"
                name="email"
                value="{{ request('email') }}"
                placeholder="Search by email"
                class="rounded-md border-gray-300 shadow-sm text-sm"
            >
            <select name="role" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>
                        {{ \App\Enums\StaffRole::tryFromName($role)?->label() ?? str_replace('-', ' ', ucfirst($role)) }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Enums\UserStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="sort" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="created_at" @selected(request('sort', 'created_at') === 'created_at')>Sort: Created date</option>
                <option value="last_login_at" @selected(request('sort') === 'last_login_at')>Sort: Last login</option>
            </select>
            <select name="direction" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="desc" @selected(request('direction', 'desc') === 'desc')>Newest first</option>
                <option value="asc" @selected(request('direction') === 'asc')>Oldest first</option>
            </select>
            <div class="md:col-span-3 xl:col-span-6 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Apply filters</button>
                @if (request()->hasAny(['name', 'email', 'role', 'status', 'sort', 'direction']))
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @if ($users->count() > 0 && (auth()->user()->can('users.update') || auth()->user()->can('users.delete')))
        <form method="POST" action="{{ route('admin.users.bulk') }}" id="bulk-user-form" class="mb-4">
            @csrf
            <input type="hidden" name="action" id="bulk-action-input" value="">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200/60">
                <span class="text-sm text-gray-600">Bulk actions:</span>
                @can('users.update')
                    <button
                        type="submit"
                        onclick="document.getElementById('bulk-action-input').value='activate'; return confirm('Activate selected users?');"
                        class="px-3 py-1.5 text-sm rounded-md border border-gray-300 hover:bg-gray-50"
                    >
                        Activate
                    </button>
                    <button
                        type="submit"
                        onclick="document.getElementById('bulk-action-input').value='deactivate'; return confirm('Deactivate selected users?');"
                        class="px-3 py-1.5 text-sm rounded-md border border-gray-300 hover:bg-gray-50"
                    >
                        Deactivate
                    </button>
                @endcan
                @can('users.delete')
                    <button
                        type="submit"
                        onclick="document.getElementById('bulk-action-input').value='delete'; return confirm('Delete selected users? This can be restored from the database if needed.');"
                        class="px-3 py-1.5 text-sm rounded-md border border-red-200 text-red-600 hover:bg-red-50"
                    >
                        Delete
                    </button>
                @endcan
            </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @if ($users->count() > 0 && (auth()->user()->can('users.update') || auth()->user()->can('users.delete')))
                        <th class="px-4 py-3 w-10">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                onclick="document.querySelectorAll('.user-row-checkbox').forEach(cb => cb.checked = this.checked)"
                                aria-label="Select all users"
                            >
                        </th>
                    @endif
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Role</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr>
                        @if ($users->count() > 0 && (auth()->user()->can('users.update') || auth()->user()->can('users.delete')))
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    name="user_ids[]"
                                    value="{{ $user->id }}"
                                    form="bulk-user-form"
                                    class="user-row-checkbox rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                    aria-label="Select {{ $user->full_name }}"
                                >
                            </td>
                        @endif
                        <td class="px-4 py-3 font-medium">{{ $user->full_name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <x-role-badge :role="$user->roles->first()?->name" />
                        </td>
                        <td class="px-4 py-3">
                            <x-user-status-badge :status="$user->status" />
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-slate-600 hover:text-slate-900">View</a>
                            @can('update', $user)
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No staff users found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($users->hasPages())
            <div class="px-4 py-3 border-t">{{ $users->links() }}</div>
        @endif
    </div>

    @if ($users->count() > 0 && (auth()->user()->can('users.update') || auth()->user()->can('users.delete')))
        </form>
    @endif
@endsection
