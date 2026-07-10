@extends('layouts.admin')

@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
    @include('admin.partials.page-header', ['title' => $user->exists ? 'Edit User' : 'Add User'])

    @if ($user->exists)
        <p class="-mt-2 mb-6">
            <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-indigo-600 hover:text-indigo-800">← View profile</a>
        </p>
    @endif

    <form
        method="POST"
        action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
        enctype="multipart/form-data"
        class="max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-6"
    >
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="first_name" value="First Name" />
                <x-text-input
                    id="first_name"
                    name="first_name"
                    class="block mt-1 w-full"
                    :value="old('first_name', $user->first_name)"
                    required
                    autofocus
                />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="last_name" value="Last Name" />
                <x-text-input
                    id="last_name"
                    name="last_name"
                    class="block mt-1 w-full"
                    :value="old('last_name', $user->last_name)"
                    required
                />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="block mt-1 w-full"
                :value="old('email', $user->email)"
                required
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input
                id="phone"
                name="phone"
                type="tel"
                class="block mt-1 w-full"
                :value="old('phone', $user->phone)"
                placeholder="+92-300-0000000"
            />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="{{ $user->exists ? 'New Password' : 'Password' }}" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="block mt-1 w-full"
                    :required="! $user->exists"
                    autocomplete="new-password"
                />
                @if ($user->exists)
                    <p class="mt-1 text-xs text-gray-500">Leave blank to keep the current password.</p>
                @endif
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirm Password" />
                <x-text-input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="block mt-1 w-full"
                    :required="! $user->exists"
                    autocomplete="new-password"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                    <option value="">Select role</option>
                    @foreach ($roles as $role)
                        <option
                            value="{{ $role->name }}"
                            @selected(old('role', $user->roles->first()?->name) === $role->name)
                        >
                            {{ \App\Enums\StaffRole::tryFromName($role->name)?->label() ?? str_replace('-', ' ', ucfirst($role->name)) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                    @foreach (\App\Enums\UserStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $user->status?->value ?? 'active') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Active: full access. Inactive: cannot access ERP. Suspended: cannot sign in.
                </p>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="profile_photo" value="Profile Image" />
            @if ($user->exists && $user->profile_photo_url)
                <div class="mt-2 mb-3 flex items-center gap-4">
                    <img
                        src="{{ $user->profile_photo_url }}"
                        alt="{{ $user->full_name }}"
                        class="h-16 w-16 rounded-full object-cover ring-2 ring-gray-100"
                    />
                    <p class="text-xs text-gray-500">Upload a new image to replace the current photo.</p>
                </div>
            @endif
            <input
                id="profile_photo"
                name="profile_photo"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
            />
            <p class="mt-1 text-xs text-gray-500">Optional. JPG, PNG, or WebP up to 2 MB.</p>
            <x-input-error :messages="$errors->get('profile_photo')" class="mt-2" />
        </div>

        @if ($user->exists)
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Role assignment</h3>
                        <p class="mt-1 text-xs text-gray-500">Uses existing Spatie roles and permissions.</p>
                    </div>
                    <x-role-badge :role="$user->roles->first()?->name" />
                </div>

                @if ($rolePermissions->isNotEmpty())
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Role permissions</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($rolePermissions->take(12) as $permission)
                                <span class="rounded bg-white px-2 py-0.5 text-xs text-gray-600 ring-1 ring-gray-200">{{ $permission }}</span>
                            @endforeach
                            @if ($rolePermissions->count() > 12)
                                <span class="rounded bg-white px-2 py-0.5 text-xs text-gray-500 ring-1 ring-gray-200">+{{ $rolePermissions->count() - 12 }} more</span>
                            @endif
                        </div>
                    </div>
                @endif

                @can('removeRole', $user)
                    <form
                        method="POST"
                        action="{{ route('admin.users.role.destroy', $user) }}"
                        onsubmit="return confirm('Remove the staff role from this user? They will lose ERP access.');"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                            Remove role
                        </button>
                    </form>
                @endcan
            </div>
        @endif

        <div class="flex gap-3 border-t border-gray-100 pt-6">
            <x-primary-button>{{ $user->exists ? 'Save Changes' : 'Create User' }}</x-primary-button>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
@endsection
