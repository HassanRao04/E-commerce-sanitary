@extends('layouts.admin')

@section('title', 'Influencers')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Influencers',
        'permission' => 'users.create',
        'actionRoute' => route('admin.influencers.create'),
        'actionLabel' => 'Add Influencer',
    ])

    <p class="mb-4 text-sm text-gray-500">Users with the Influencer role. Profiles are normal users managed in the Users module.</p>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Phone</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Created Date</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $user->full_name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->phone ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-user-status-badge :status="$user->status" />
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                            {{ $user->created_at?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-slate-600 hover:text-slate-900">View</a>
                                @can('update', $user)
                                    <a href="{{ route('admin.influencers.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                                    @if ($user->status !== \App\Enums\UserStatus::Active)
                                        <form method="POST" action="{{ route('admin.influencers.activate', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800">Activate</button>
                                        </form>
                                    @endif
                                    @if ($user->status !== \App\Enums\UserStatus::Inactive)
                                        <form method="POST" action="{{ route('admin.influencers.deactivate', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-amber-600 hover:text-amber-800">Deactivate</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('delete', $user)
                                    <form method="POST" action="{{ route('admin.influencers.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete this influencer? Soft-deleted users can be restored from the database if needed.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No influencer users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($users->hasPages())
            <div class="px-4 py-3 border-t">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
