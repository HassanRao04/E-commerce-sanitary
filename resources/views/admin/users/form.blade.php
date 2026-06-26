@extends('layouts.admin')

@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
    @include('admin.partials.page-header', ['title' => $user->exists ? 'Edit User' : 'Add User'])

    <div class="max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
        <p class="text-sm text-gray-600">User management forms will be completed in a later phase. Use seeded admin accounts for now.</p>
        <div class="mt-4">
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← Back to users</a>
        </div>
    </div>
@endsection
