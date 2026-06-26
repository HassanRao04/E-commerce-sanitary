@extends('layouts.admin')

@section('title', $category->exists ? 'Edit Category' : 'Create Category')

@section('content')
    @include('admin.partials.page-header', ['title' => $category->exists ? 'Edit Category' : 'Create Category'])

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="bg-white rounded-lg shadow p-6 space-y-6 max-w-2xl">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $category->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="slug" value="Slug (optional — auto-generated)" />
            <x-text-input id="slug" name="slug" class="block mt-1 w-full" :value="old('slug', $category->slug)" />
        </div>
        <div>
            <x-input-label for="parent_id" value="Parent Category" />
            <select id="parent_id" name="parent_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">— Root —</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('description', $category->description) }}</textarea>
        </div>
        <div>
            <x-input-label for="sort_order" value="Sort Order" />
            <x-text-input id="sort_order" name="sort_order" type="number" class="block mt-1 w-full" :value="old('sort_order', $category->sort_order ?? 0)" />
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Active
        </label>

        <div class="flex gap-3">
            <x-primary-button>{{ $category->exists ? 'Update' : 'Create' }}</x-primary-button>
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
        </div>
    </form>
@endsection
