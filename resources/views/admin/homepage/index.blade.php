@extends('layouts.admin')

@section('title', 'Website Content')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Website Content',
        'permission' => 'homepage.manage',
        'actionRoute' => route('admin.homepage.hero.create'),
        'actionLabel' => 'Add Hero Slide',
    ])

    <nav class="mb-6 flex flex-wrap gap-2 text-sm">
        <a href="#social-media" class="rounded-full bg-white px-4 py-2 ring-1 ring-gray-200 text-gray-700 hover:text-indigo-700 hover:ring-indigo-200">Social media</a>
        <a href="#header-settings" class="rounded-full bg-white px-4 py-2 ring-1 ring-gray-200 text-gray-700 hover:text-indigo-700 hover:ring-indigo-200">Header &amp; menu</a>
        <a href="#homepage-sections" class="rounded-full bg-white px-4 py-2 ring-1 ring-gray-200 text-gray-700 hover:text-indigo-700 hover:ring-indigo-200">Homepage sections</a>
        <a href="#footer-content" class="rounded-full bg-white px-4 py-2 ring-1 ring-gray-200 text-gray-700 hover:text-indigo-700 hover:ring-indigo-200">Footer</a>
        <a href="#contact-content" class="rounded-full bg-white px-4 py-2 ring-1 ring-gray-200 text-gray-700 hover:text-indigo-700 hover:ring-indigo-200">Contact</a>
    </nav>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1">
            <form method="POST" action="{{ route('admin.homepage.branding.update') }}" enctype="multipart/form-data" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <h3 class="font-semibold text-gray-900">Branding</h3>
                    <p class="mt-1 text-sm text-gray-500">Upload your storefront logo and favicon. Requires <code class="text-xs bg-gray-100 px-1 rounded">homepage.manage</code> permission.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Site Logo</label>
                    @if ($settings->logo_url)
                        <div class="mt-2 mb-3 flex items-center gap-4">
                            <img src="{{ $settings->logo_url }}" alt="Current logo" class="h-12 max-w-[180px] object-contain rounded border border-gray-200 bg-gray-50 p-2">
                            @can('homepage.manage')
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="remove_logo" value="1"> Remove logo
                                </label>
                            @endcan
                        </div>
                    @endif
                    @can('homepage.manage')
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="mt-1 block w-full text-sm text-gray-600">
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, WebP or SVG. Max 2 MB. Shown in header and footer.</p>
                    @else
                        <p class="mt-2 text-sm text-gray-500">No logo uploaded yet.</p>
                    @endcan
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Favicon</label>
                    @if ($settings->favicon_url)
                        <div class="mt-2 mb-3 flex items-center gap-4">
                            <img src="{{ $settings->favicon_url }}" alt="Current favicon" class="h-8 w-8 object-contain rounded border border-gray-200 bg-gray-50 p-1">
                            @can('homepage.manage')
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="remove_favicon" value="1"> Remove favicon
                                </label>
                            @endcan
                        </div>
                    @endif
                    @can('homepage.manage')
                        <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/x-icon,.ico" class="mt-1 block w-full text-sm text-gray-600">
                        <p class="mt-1 text-xs text-gray-500">PNG or ICO. Max 1 MB.</p>
                        <x-primary-button class="mt-4">Save Branding</x-primary-button>
                    @endcan
                    <x-input-error :messages="$errors->get('favicon')" class="mt-2" />
                </div>
            </form>
        </div>

        <div class="xl:col-span-2">
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Hero Slides</h3>
                        <p class="text-sm text-gray-500">Manage homepage carousel images and copy.</p>
                    </div>
                </div>

                @if ($heroBanners->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        <p>No hero slides yet. Default demo slides are shown on the storefront until you add your own.</p>
                        @can('homepage.manage')
                            <a href="{{ route('admin.homepage.hero.create') }}" class="inline-block mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-800">Create first slide</a>
                        @endcan
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Preview</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Title</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Order</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($heroBanners as $banner)
                                    <tr>
                                        <td class="px-4 py-3">
                                            @if ($banner->image_url)
                                                <img src="{{ $banner->image_url }}" alt="" class="h-12 w-20 object-cover rounded-md ring-1 ring-gray-200">
                                            @else
                                                <span class="inline-flex h-12 w-20 items-center justify-center rounded-md bg-gray-100 text-xs text-gray-500">No image</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-gray-900">{{ $banner->title }}</p>
                                            <p class="text-gray-500 truncate max-w-xs">{{ $banner->subtitle }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $banner->sort_order }}</td>
                                        <td class="px-4 py-3">
                                            @if ($banner->is_active)
                                                <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Hidden</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @can('homepage.manage')
                                                <a href="{{ route('admin.homepage.hero.edit', $banner) }}" class="text-indigo-600 hover:text-indigo-800 mr-3">Edit</a>
                                                <form method="POST" action="{{ route('admin.homepage.hero.destroy', $banner) }}" class="inline" onsubmit="return confirm('Delete this hero slide?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            @else
                                                <span class="text-gray-400">View only</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('admin.homepage.partials.social-fields')

    @include('admin.homepage.partials.header-fields')

    <div id="homepage-sections" class="mt-8 rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Homepage sections</h3>
            <p class="text-sm text-gray-500">Manage featured, trending, flash sale carousels, category headings, testimonials, newsletter, and other storefront blocks. Requires <code class="text-xs bg-gray-100 px-1 rounded">homepage.manage</code>.</p>
        </div>

        @can('homepage.manage')
            <form method="POST" action="{{ route('admin.homepage.sections.update') }}" class="p-6 space-y-4">
                @csrf
                @method('PATCH')

                @foreach ($sectionKeys as $key)
                    @include('admin.homepage.partials.section-fields', [
                        'key' => $key,
                        'section' => $sections[$key] ?? [],
                        'categoryOptions' => $categoryOptions,
                    ])
                @endforeach

                <div class="pt-2">
                    <x-primary-button>Save homepage sections</x-primary-button>
                </div>
            </form>
        @else
            <div class="p-6 text-sm text-gray-500">You have view-only access to homepage settings.</div>
        @endcan
    </div>

    @include('admin.homepage.partials.footer-fields')

    @include('admin.homepage.partials.contact-fields')
@endsection
