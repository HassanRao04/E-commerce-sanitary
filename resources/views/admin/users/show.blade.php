@extends('layouts.admin')

@section('title', $user->full_name)

@section('content')
    @include('admin.partials.page-header', ['title' => 'User Profile'])

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-6 py-8">
                    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-end">
                        <x-user-avatar :user="$user" size="xl" />
                        <div class="text-center sm:text-left">
                            <h2 class="text-2xl font-semibold text-white">{{ $user->full_name }}</h2>
                            <p class="mt-1 text-sm text-slate-300">{{ $user->email }}</p>
                            <div class="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                <x-role-badge :role="$user->roles->first()?->name" />
                                <x-user-status-badge :status="$user->status" />
                            </div>
                        </div>
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-6 p-6 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Email</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Phone</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $user->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Role</dt>
                        <dd class="mt-1">
                            @if ($user->roles->first())
                                <x-role-badge :role="$user->roles->first()->name" />
                            @else
                                <span class="text-gray-500">No role assigned</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</dt>
                        <dd class="mt-1"><x-user-status-badge :status="$user->status" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Last Login</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            @if ($user->last_login_at)
                                {{ $user->last_login_at->format('M j, Y g:i A') }}
                                @if ($user->last_login_ip)
                                    <span class="block text-xs font-normal text-gray-500">{{ $user->last_login_ip }}</span>
                                @endif
                            @else
                                <span class="text-gray-500">Never</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Account Created</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $user->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
                <div class="border-b border-gray-100 px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                        <p class="mt-1 text-sm text-gray-500">Account changes, authentication, and role events.</p>
                    </div>
                    @can('viewAny', App\Models\ActivityLog::class)
                        <a
                            href="{{ route('admin.activity.index', ['subject' => $user->id]) }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            View all logs →
                        </a>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Event</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Performed By</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">IP</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Browser</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($activityLogs as $log)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $log->action_label }}</p>
                                        @if ($log->description)
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $log->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $log->user?->full_name ?? 'System' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $log->browser ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $log->created_at?->format('M j, Y g:i A') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        No activity recorded for this user yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
                <h3 class="text-sm font-semibold text-gray-900">Actions</h3>
                <div class="mt-4 space-y-2">
                    @can('update', $user)
                        <a
                            href="{{ route('admin.users.edit', $user) }}"
                            class="block rounded-md bg-slate-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-slate-800"
                        >
                            Edit User
                        </a>
                    @endcan
                    @can('delete', $user)
                        <form
                            method="POST"
                            action="{{ route('admin.users.destroy', $user) }}"
                            onsubmit="return confirm('Delete this user? This action can be reversed by restoring from the database.');"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="block w-full rounded-md border border-red-200 px-4 py-2 text-center text-sm font-medium text-red-600 hover:bg-red-50"
                            >
                                Delete User
                            </button>
                        </form>
                    @endcan
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="block rounded-md px-4 py-2 text-center text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                    >
                        ← Back to users
                    </a>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 text-sm">
                <h3 class="text-sm font-semibold text-gray-900">Account Summary</h3>
                <ul class="mt-4 space-y-3 text-gray-600">
                    <li class="flex justify-between gap-4">
                        <span>Staff member</span>
                        <span class="font-medium text-gray-900">{{ $user->isStaff() ? 'Yes' : 'No' }}</span>
                    </li>
                    <li class="flex justify-between gap-4">
                        <span>Email verified</span>
                        <span class="font-medium text-gray-900">{{ $user->is_verified ? 'Yes' : 'No' }}</span>
                    </li>
                    <li class="flex justify-between gap-4">
                        <span>Activity entries</span>
                        <span class="font-medium text-gray-900">{{ $activityLogs->count() }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
