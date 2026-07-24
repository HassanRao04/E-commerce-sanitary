@extends('layouts.admin')

@section('title', $influencer->exists ? 'Edit Influencer' : 'Add Influencer')

@section('content')
    @include('admin.partials.page-header', [
        'title' => $influencer->exists ? 'Edit Influencer' : 'Add Influencer',
    ])

    <p class="-mt-2 mb-6">
        <a href="{{ route('admin.influencers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Influencers</a>
    </p>

    <form
        method="POST"
        action="{{ $influencer->exists ? route('admin.influencers.update', $influencer) : route('admin.influencers.store') }}"
        enctype="multipart/form-data"
        class="max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-6"
    >
        @csrf
        @if ($influencer->exists)
            @method('PUT')
        @endif

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input
                id="name"
                name="name"
                class="block mt-1 w-full"
                :value="old('name', $influencer->full_name)"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="block mt-1 w-full"
                :value="old('email', $influencer->email)"
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
                :value="old('phone', $influencer->phone)"
                placeholder="+92-300-0000000"
                required
            />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="{{ $influencer->exists ? 'New Password' : 'Password' }}" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="block mt-1 w-full"
                    :required="! $influencer->exists"
                    autocomplete="new-password"
                />
                @if ($influencer->exists)
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
                    :required="! $influencer->exists"
                    autocomplete="new-password"
                />
            </div>
        </div>

        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                @foreach (\App\Enums\UserStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $influencer->status?->value ?? 'active') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="notes" value="Notes" />
            <textarea
                id="notes"
                name="notes"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 text-sm"
            >{{ old('notes', $influencer->notes) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Optional.</p>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="profile_photo" value="Profile Image" />
            @if ($influencer->exists && $influencer->profile_photo_url)
                <div class="mt-2 mb-3 flex items-center gap-4">
                    <img
                        src="{{ $influencer->profile_photo_url }}"
                        alt="{{ $influencer->full_name }}"
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

        <div class="flex gap-3 border-t border-gray-100 pt-6">
            <x-primary-button>{{ $influencer->exists ? 'Save Changes' : 'Create Influencer' }}</x-primary-button>
            <a href="{{ route('admin.influencers.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
@endsection
