@php
    $announcement = $storefrontHeader['announcement'] ?? [];
    $navItems = $storefrontHeader['nav_items'] ?? [];
@endphp

<div id="header-settings" class="mt-8 rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900">Header &amp; announcement bar</h3>
        <p class="text-sm text-gray-500">Edit the top promo strip and primary navigation menu shown on every storefront page.</p>
    </div>

    @can('homepage.manage')
        <form
            method="POST"
            action="{{ route('admin.homepage.header.update') }}"
            class="p-6 space-y-8"
            x-data="headerNavEditor(@js($navItems))"
        >
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div>
                    <h4 class="font-medium text-gray-900">Announcement bar</h4>
                    <p class="text-sm text-gray-500">The thin bar above the logo on all pages.</p>
                </div>

                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="hidden" name="header[announcement][enabled]" value="0">
                    <input type="checkbox" name="header[announcement][enabled]" value="1" @checked(old('header.announcement.enabled', $announcement['enabled'] ?? true))>
                    Show announcement bar
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea name="header[announcement][text]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('header.announcement.text', $announcement['text'] ?? '') }}</textarea>
                    <x-input-error :messages="$errors->get('header.announcement.text')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Optional link URL</label>
                        <input type="text" name="header[announcement][link_url]" value="{{ old('header.announcement.link_url', $announcement['link_url'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="/shop/products">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Optional link label</label>
                        <input type="text" name="header[announcement][link_label]" value="{{ old('header.announcement.link_label', $announcement['link_label'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Shop now">
                    </div>
                </div>
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h4 class="font-medium text-gray-900">Top navigation menu</h4>
                        <p class="text-sm text-gray-500">Desktop menu below the logo. Mobile menu uses the same links.</p>
                    </div>
                    <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="addItem()">Add link</button>
                </div>

                <template x-for="(item, index) in items" :key="index">
                    <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-gray-900" x-text="'Link ' + (index + 1)"></p>
                            <div class="flex items-center gap-2">
                                <button type="button" class="text-xs text-gray-500 hover:text-gray-800" @click="moveItem(index, -1)">Up</button>
                                <button type="button" class="text-xs text-gray-500 hover:text-gray-800" @click="moveItem(index, 1)">Down</button>
                                <button type="button" class="text-xs text-red-600 hover:text-red-800" @click="removeItem(index)">Remove</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Label</label>
                                <input type="text" :name="'header[nav_items][' + index + '][label]'" x-model="item.label" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Route</label>
                                <select :name="'header[nav_items][' + index + '][route]'" x-model="item.route" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">Custom URL</option>
                                    @foreach ($routeOptions as $routeName)
                                        <option value="{{ $routeName }}">{{ $routeName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Custom URL</label>
                                <input type="text" :name="'header[nav_items][' + index + '][url]'" x-model="item.url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="/promo">
                                <p class="mt-1 text-xs text-gray-500">Use when Route is blank.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Active route patterns</label>
                                <input type="text" :name="'header[nav_items][' + index + '][active_patterns]'" x-model="item.active_patterns" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="shop.products.*, shop.categories.*">
                                <p class="mt-1 text-xs text-gray-500">Comma-separated route names for highlight state.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort order</label>
                                <input type="number" min="0" max="999" :name="'header[nav_items][' + index + '][sort_order]'" x-model="item.sort_order" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-5 text-sm">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" :name="'header[nav_items][' + index + '][enabled]'" value="0">
                                <input type="checkbox" :name="'header[nav_items][' + index + '][enabled]'" value="1" x-model="item.enabled">
                                Visible
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" :name="'header[nav_items][' + index + '][mega_menu]'" value="0">
                                <input type="checkbox" :name="'header[nav_items][' + index + '][mega_menu]'" value="1" x-model="item.mega_menu">
                                Mega menu (categories dropdown)
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" :name="'header[nav_items][' + index + '][open_in_new_tab]'" value="0">
                                <input type="checkbox" :name="'header[nav_items][' + index + '][open_in_new_tab]'" value="1" x-model="item.open_in_new_tab">
                                Open in new tab
                            </label>
                        </div>
                    </div>
                </template>

                <x-input-error :messages="$errors->get('header.nav_items')" class="mt-2" />
            </div>

            <x-primary-button>Save header &amp; announcement</x-primary-button>
        </form>
    @else
        <div class="p-6 text-sm text-gray-500">You have view-only access to header settings.</div>
    @endcan
</div>
