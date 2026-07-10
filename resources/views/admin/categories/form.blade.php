@extends('layouts.admin')

@section('title', $category->exists ? 'Edit Category' : 'Create Category')

@section('content')
    @include('admin.partials.page-header', ['title' => $category->exists ? 'Edit Category' : 'Create Category'])

    <form
        method="POST"
        action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
        enctype="multipart/form-data"
        class="max-w-3xl space-y-6"
    >
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Category details</h3>

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $category->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="slug" value="Slug (optional — auto-generated)" />
                    <x-text-input id="slug" name="slug" class="block mt-1 w-full" :value="old('slug', $category->slug)" />
                </div>
                <div>
                    <x-input-label for="sort_order" value="Sort Order" />
                    <x-text-input id="sort_order" name="sort_order" type="number" class="block mt-1 w-full" :value="old('sort_order', $category->sort_order ?? 0)" />
                    <p class="mt-1 text-xs text-gray-500">Lower numbers appear first on the homepage.</p>
                </div>
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

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Active
            </label>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <div>
                <h3 class="font-semibold text-gray-900">Homepage card image</h3>
                <p class="mt-1 text-sm text-gray-500">Shown in the <strong>Shop by category</strong> section on the homepage. Requires <code class="text-xs bg-gray-100 px-1 rounded">categories.manage</code> permission.</p>
            </div>

            @if ($category->image_url)
                <div class="flex items-start gap-4">
                    <img src="{{ $category->image_url }}" alt="" class="h-24 w-24 object-cover rounded-lg ring-1 ring-gray-200">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_image" value="1"> Remove current image
                    </label>
                </div>
            @endif

            <div>
                <x-input-label for="image" value="Upload card image" />
                <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm text-gray-600">
                <p class="mt-1 text-xs text-gray-500">Square or 4:3 works best. PNG, JPG, or WebP. Max 2 MB.</p>
                <x-input-error :messages="$errors->get('image')" class="mt-2" />
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <div>
                <h3 class="font-semibold text-gray-900">Category page banner (optional)</h3>
                <p class="mt-1 text-sm text-gray-500">Used as a fallback image if no card image is set, and for future category landing pages.</p>
            </div>

            @if ($category->banner_url)
                <div class="flex items-start gap-4">
                    <img src="{{ $category->banner_url }}" alt="" class="h-20 w-40 object-cover rounded-lg ring-1 ring-gray-200">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_banner_image" value="1"> Remove banner
                    </label>
                </div>
            @endif

            <div>
                <x-input-label for="banner_image" value="Upload banner image" />
                <input type="file" id="banner_image" name="banner_image" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm text-gray-600">
                <p class="mt-1 text-xs text-gray-500">Wide image recommended (1200×400+). Max 4 MB.</p>
                <x-input-error :messages="$errors->get('banner_image')" class="mt-2" />
            </div>
        </div>

        <div class="flex gap-3">
            <x-primary-button>{{ $category->exists ? 'Update' : 'Create' }}</x-primary-button>
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
        </div>
    </form>
@endsection
