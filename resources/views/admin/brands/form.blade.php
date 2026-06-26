@extends('layouts.admin')

@section('title', $brand->exists ? 'Edit Brand' : 'Create Brand')

@section('content')
    @include('admin.partials.page-header', ['title' => $brand->exists ? 'Edit Brand' : 'Create Brand'])

    <form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" class="bg-white rounded-lg shadow p-6 space-y-6 max-w-2xl">
        @csrf
        @if ($brand->exists) @method('PUT') @endif

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $brand->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="slug" value="Slug (optional)" />
            <x-text-input id="slug" name="slug" class="block mt-1 w-full" :value="old('slug', $brand->slug)" />
        </div>
        <div>
            <x-input-label for="website" value="Website" />
            <x-text-input id="website" name="website" type="url" class="block mt-1 w-full" :value="old('website', $brand->website)" />
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="4" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('description', $brand->description) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active ?? true))> Active
        </label>

        <div class="flex gap-3">
            <x-primary-button>{{ $brand->exists ? 'Update' : 'Create' }}</x-primary-button>
            <a href="{{ route('admin.brands.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
        </div>
    </form>
@endsection
