@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit Hero Slide' : 'Add Hero Slide')

@section('content')
    @include('admin.partials.page-header', ['title' => $banner->exists ? 'Edit Hero Slide' : 'Add Hero Slide'])

    @php
        $meta = $banner->metadata ?? [];
    @endphp

    <form
        method="POST"
        action="{{ $banner->exists ? route('admin.homepage.hero.update', $banner) : route('admin.homepage.hero.store') }}"
        enctype="multipart/form-data"
        class="max-w-3xl space-y-6"
    >
        @csrf
        @if ($banner->exists) @method('PUT') @endif

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Slide image</h3>

            @if ($banner->image_url)
                <div class="flex items-start gap-4">
                    <img src="{{ $banner->image_url }}" alt="" class="h-28 w-48 object-cover rounded-lg ring-1 ring-gray-200">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_image" value="1"> Remove current image
                    </label>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700">Hero background image {{ $banner->exists ? '(optional)' : '' }}</label>
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm text-gray-600">
                <p class="mt-1 text-xs text-gray-500">Recommended 1920×900 or wider. Max 4 MB. Used as the slide background.</p>
                <x-input-error :messages="$errors->get('image')" class="mt-2" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Fallback gradient (CSS)</label>
                <input type="text" name="background" value="{{ old('background', $meta['background'] ?? '') }}" placeholder="linear-gradient(135deg, #0b0b0f 0%, #003566 100%)" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                <p class="mt-1 text-xs text-gray-500">Used when no image is uploaded.</p>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Slide copy</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" value="{{ old('title', $banner->title) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Eyebrow</label>
                    <input type="text" name="eyebrow" value="{{ old('eyebrow', $meta['eyebrow'] ?? '') }}" placeholder="Just dropped" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Subtitle</label>
                <textarea name="subtitle" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('subtitle', $banner->subtitle) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Badge label</label>
                    <input type="text" name="badge" value="{{ old('badge', $meta['badge'] ?? '') }}" placeholder="New arrivals" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sort order (slide slot)</label>
                    <input type="number" name="sort_order" min="0" max="3" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">0 = first slide, 1 = second, 2 = third, 3 = fourth. Your image replaces the background for that slot; default text is kept unless you fill the fields below.</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Buttons & promo card</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Primary button label</label>
                    <input type="text" name="button_text" value="{{ old('button_text', $banner->button_text) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Primary button URL</label>
                    <input type="text" name="button_url" value="{{ old('button_url', $banner->button_url) }}" placeholder="/shop/products" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Secondary button label</label>
                    <input type="text" name="secondary_button_text" value="{{ old('secondary_button_text', $meta['secondary_button_text'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Secondary button URL</label>
                    <input type="text" name="secondary_button_url" value="{{ old('secondary_button_url', $meta['secondary_button_url'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Promo headline</label>
                    <input type="text" name="promo" value="{{ old('promo', $meta['promo'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Promo detail</label>
                    <input type="text" name="promo_detail" value="{{ old('promo_detail', $meta['promo_detail'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active ?? true))> Active (visible on homepage)
            </label>
        </div>

        <div class="flex gap-3">
            <x-primary-button>{{ $banner->exists ? 'Update slide' : 'Create slide' }}</x-primary-button>
            <a href="{{ route('admin.homepage.index') }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
        </div>
    </form>
@endsection
