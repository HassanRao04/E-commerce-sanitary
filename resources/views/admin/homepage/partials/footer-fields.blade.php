@php
    $footer = $storefrontFooter ?? \App\Support\StorefrontFooter::resolved();
@endphp

<div id="footer-content" class="mt-8 rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900">Footer content</h3>
        <p class="text-sm text-gray-500">Manage footer tagline, newsletter copy, link columns, and featured categories shown site-wide.</p>
    </div>

    @can('homepage.manage')
        <form method="POST" action="{{ route('admin.homepage.footer.update') }}" class="p-6 space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-gray-700">Tagline</label>
                <textarea name="footer[tagline]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('footer.tagline', $footer['tagline'] ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Copyright name</label>
                    <input type="text" name="footer[copyright_name]" value="{{ old('footer.copyright_name', $footer['copyright_name'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bottom meta line</label>
                    <input type="text" name="footer[bottom_meta]" value="{{ old('footer.bottom_meta', $footer['bottom_meta'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Newsletter title</label>
                    <input type="text" name="footer[newsletter][title]" value="{{ old('footer.newsletter.title', $footer['newsletter']['title'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Newsletter copy</label>
                    <input type="text" name="footer[newsletter][copy]" value="{{ old('footer.newsletter.copy', $footer['newsletter']['copy'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                <h4 class="font-medium text-gray-900">Footer categories column</h4>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="footer[categories][mode]" value="auto" @checked(old('footer.categories.mode', $footer['categories']['mode'] ?? 'auto') === 'auto')>
                        Automatic (top root categories)
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="footer[categories][mode]" value="manual" @checked(old('footer.categories.mode', $footer['categories']['mode'] ?? 'auto') === 'manual')>
                        Hand-picked categories
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Items to show</label>
                    <input type="number" min="1" max="12" name="footer[categories][limit]" value="{{ old('footer.categories.limit', $footer['categories']['limit'] ?? 6) }}" class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach ($categoryOptions as $category)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="footer[categories][category_ids][]" value="{{ $category->id }}" @checked(in_array($category->id, old('footer.categories.category_ids', $footer['categories']['category_ids'] ?? []), true))>
                            {{ $category->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <x-primary-button>Save footer content</x-primary-button>
        </form>
    @else
        <div class="p-6 text-sm text-gray-500">You have view-only access to footer settings.</div>
    @endcan
</div>
