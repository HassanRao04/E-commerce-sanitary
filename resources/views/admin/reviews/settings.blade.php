@extends('layouts.admin')

@section('title', 'Review Settings')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Review Settings'])
    <p class="-mt-4 mb-6 text-sm text-gray-500">Configure storefront reviews, approval workflow, and homepage testimonials.</p>

    <form method="POST" action="{{ route('admin.reviews.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PATCH')

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <label class="flex items-center gap-3">
                <input type="hidden" name="reviews_enabled" value="0">
                <input type="checkbox" name="reviews_enabled" value="1" @checked(old('reviews_enabled', $settings->reviews_enabled))>
                <span class="text-sm font-medium text-gray-900">Reviews enabled on storefront</span>
            </label>

            <label class="flex items-center gap-3">
                <input type="hidden" name="auto_approve" value="0">
                <input type="checkbox" name="auto_approve" value="1" @checked(old('auto_approve', $settings->auto_approve))>
                <span class="text-sm font-medium text-gray-900">Auto-approve new reviews</span>
            </label>

            <label class="flex items-center gap-3">
                <input type="hidden" name="show_on_homepage" value="0">
                <input type="checkbox" name="show_on_homepage" value="1" @checked(old('show_on_homepage', $settings->show_on_homepage))>
                <span class="text-sm font-medium text-gray-900">Show reviews on homepage testimonials section</span>
            </label>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="max_featured" class="block text-sm font-medium text-gray-700">Maximum featured reviews</label>
                <input type="number" name="max_featured" id="max_featured" min="1" max="12"
                       value="{{ old('max_featured', $settings->max_featured) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label for="homepage_mode" class="block text-sm font-medium text-gray-700">Homepage review source</label>
                <select name="homepage_mode" id="homepage_mode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="featured" @selected(old('homepage_mode', $settings->homepage_mode) === 'featured')>Featured reviews only</option>
                    <option value="latest" @selected(old('homepage_mode', $settings->homepage_mode) === 'latest')>Latest approved reviews</option>
                </select>
            </div>
        </div>

        <x-primary-button>Save review settings</x-primary-button>
    </form>
@endsection
