@php
    use App\Support\HomepageSections;

    $selectedProducts = $sectionProducts[$key] ?? collect();
@endphp

<details class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60 overflow-hidden group" {{ $loop->first ? 'open' : '' }}>
    <summary class="cursor-pointer list-none px-6 py-4 flex items-center justify-between gap-4 border-b border-gray-100 group-open:border-b">
        <div>
            <h3 class="font-semibold text-gray-900">{{ HomepageSections::label($key) }}</h3>
            <p class="text-sm text-gray-500">
                @if (in_array($key, HomepageSections::carouselKeys(), true))
                    Control title, styling, visibility, and which products appear.
                @elseif ($key === HomepageSections::TRUST)
                    Show or hide trust, shipping, security, and payment blocks.
                @else
                    Control section copy and visibility on the storefront homepage.
                @endif
            </p>
        </div>
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Configure</span>
    </summary>

    <div class="p-6 space-y-4">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <input type="hidden" name="sections[{{ $key }}][enabled]" value="0">
            <input type="checkbox" name="sections[{{ $key }}][enabled]" value="1" @checked(old("sections.{$key}.enabled", $section['enabled'] ?? true))>
            Visible on homepage
        </label>

        @if (in_array($key, HomepageSections::carouselKeys(), true))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="sections[{{ $key }}][title]" value="{{ old("sections.{$key}.title", $section['title'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Badge</label>
                    <input type="text" name="sections[{{ $key }}][badge]" value="{{ old("sections.{$key}.badge", $section['badge'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Subtitle</label>
                <textarea name="sections[{{ $key }}][subtitle]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old("sections.{$key}.subtitle", $section['subtitle'] ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Theme</label>
                    <select name="sections[{{ $key }}][theme]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach (['default' => 'Default', 'muted' => 'Muted', 'sale' => 'Sale highlight'] as $value => $label)
                            <option value="{{ $value }}" @selected(old("sections.{$key}.theme", $section['theme'] ?? 'default') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Badge CSS class</label>
                    <input type="text" name="sections[{{ $key }}][badge_class]" value="{{ old("sections.{$key}.badge_class", $section['badge_class'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="ds-badge-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product limit</label>
                    <input type="number" min="1" max="24" name="sections[{{ $key }}][limit]" value="{{ old("sections.{$key}.limit", $section['limit'] ?? 12) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">View all link label</label>
                    <input type="text" name="sections[{{ $key }}][view_all_label]" value="{{ old("sections.{$key}.view_all_label", $section['view_all_label'] ?? 'View all') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Shop collection slug</label>
                    <input type="text" name="sections[{{ $key }}][collection]" value="{{ old("sections.{$key}.collection", $section['collection'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="featured">
                    <p class="mt-1 text-xs text-gray-500">Used in the “View all” URL on the homepage.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product source</label>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="sections[{{ $key }}][mode]" value="auto" @checked(old("sections.{$key}.mode", $section['mode'] ?? 'auto') === 'auto')>
                        Automatic
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="sections[{{ $key }}][mode]" value="manual" @checked(old("sections.{$key}.mode", $section['mode'] ?? 'auto') === 'manual')>
                        Hand-picked products
                    </label>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Automatic uses product flags and sales data.
                    @if ($key === HomepageSections::FEATURED)
                        Mark products as <strong>Featured</strong> in the product editor.
                    @elseif ($key === HomepageSections::BEST_SELLERS)
                        Mark products as <strong>Best seller</strong>.
                    @elseif ($key === HomepageSections::NEW_ARRIVALS)
                        Mark products as <strong>New arrival</strong>.
                    @elseif ($key === HomepageSections::TRENDING)
                        Based on orders in the last 90 days.
                    @elseif ($key === HomepageSections::FLASH_SALE)
                        Products with an active sale price on a variant.
                    @endif
                </p>
            </div>

            <div
                x-data="homepageProductPicker({
                    sectionKey: @js($key),
                    initialProducts: @js($selectedProducts->map(fn ($product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->base_sku])->values()),
                    searchUrl: @js(route('admin.homepage.products.search')),
                })"
                class="rounded-lg border border-gray-200 p-4 space-y-3"
            >
                <label class="block text-sm font-medium text-gray-700">Hand-picked products</label>
                <div class="flex gap-2">
                    <input
                        type="search"
                        x-model="query"
                        @input.debounce.300ms="search()"
                        placeholder="Search by name or SKU…"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm"
                    >
                </div>

                <div x-show="results.length" x-cloak class="rounded-md border border-gray-200 divide-y divide-gray-100 max-h-48 overflow-y-auto">
                    <template x-for="product in results" :key="product.id">
                        <button type="button" @click="add(product)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex justify-between gap-2">
                            <span x-text="product.name"></span>
                            <span class="text-gray-400 shrink-0" x-text="product.sku"></span>
                        </button>
                    </template>
                </div>

                <div class="flex flex-wrap gap-2 min-h-[2rem]">
                    <template x-for="product in selected" :key="product.id">
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-800">
                            <span x-text="product.name"></span>
                            <button type="button" @click="remove(product.id)" class="text-indigo-500 hover:text-indigo-800">&times;</button>
                            <input type="hidden" :name="'sections[' + sectionKey + '][product_ids][]'" :value="product.id">
                        </span>
                    </template>
                    <p x-show="!selected.length" class="text-xs text-gray-500">No products selected. Search above to add items when using hand-picked mode.</p>
                </div>

                @if ($selectedProducts->isNotEmpty())
                    <noscript>
                        <p class="text-xs text-gray-500">Selected IDs: {{ $selectedProducts->pluck('id')->implode(', ') }}</p>
                    </noscript>
                @endif
            </div>
        @elseif (in_array($key, [HomepageSections::CATEGORIES, HomepageSections::BRANDS], true))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Eyebrow</label>
                    <input type="text" name="sections[{{ $key }}][eyebrow]" value="{{ old("sections.{$key}.eyebrow", $section['eyebrow'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="sections[{{ $key }}][title]" value="{{ old("sections.{$key}.title", $section['title'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Items to show</label>
                    <input type="number" min="1" max="24" name="sections[{{ $key }}][limit]" value="{{ old("sections.{$key}.limit", $section['limit'] ?? 6) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>
            @if ($key === HomepageSections::CATEGORIES)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category source</label>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="sections[{{ $key }}][mode]" value="auto" @checked(old("sections.{$key}.mode", $section['mode'] ?? 'auto') === 'auto')>
                            Automatic (top root categories)
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="sections[{{ $key }}][mode]" value="manual" @checked(old("sections.{$key}.mode", $section['mode'] ?? 'auto') === 'manual')>
                            Hand-picked categories
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach ($categoryOptions ?? [] as $category)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="sections[{{ $key }}][category_ids][]" value="{{ $category->id }}" @checked(in_array($category->id, old("sections.{$key}.category_ids", $section['category_ids'] ?? []), true))>
                            {{ $category->name }}
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500">Category cards and images are managed under <a href="{{ route('admin.categories.index') }}" class="text-indigo-600 hover:text-indigo-800">Catalog → Categories</a>.</p>
            @else
                <p class="text-xs text-gray-500">Brand logos and names are managed under <a href="{{ route('admin.brands.index') }}" class="text-indigo-600 hover:text-indigo-800">Catalog → Brands</a>.</p>
            @endif
        @elseif ($key === HomepageSections::TESTIMONIALS)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Badge</label>
                    <input type="text" name="sections[{{ $key }}][badge]" value="{{ old("sections.{$key}.badge", $section['badge'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reviews to show</label>
                    <input type="number" min="1" max="12" name="sections[{{ $key }}][limit]" value="{{ old("sections.{$key}.limit", $section['limit'] ?? 6) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="sections[{{ $key }}][title]" value="{{ old("sections.{$key}.title", $section['title'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Subtitle</label>
                <textarea name="sections[{{ $key }}][subtitle]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old("sections.{$key}.subtitle", $section['subtitle'] ?? '') }}</textarea>
            </div>
            <p class="text-xs text-gray-500">Review content is moderated under <a href="{{ route('admin.reviews.index') }}" class="text-indigo-600 hover:text-indigo-800">Engagement → Reviews</a>.</p>
        @elseif ($key === HomepageSections::NEWSLETTER)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="sections[{{ $key }}][title]" value="{{ old("sections.{$key}.title", $section['title'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Theme</label>
                    <select name="sections[{{ $key }}][theme]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="dark" @selected(old("sections.{$key}.theme", $section['theme'] ?? 'dark') === 'dark')>Dark panel</option>
                        <option value="light" @selected(old("sections.{$key}.theme", $section['theme'] ?? 'dark') === 'light')>Light panel</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Subtitle</label>
                <textarea name="sections[{{ $key }}][subtitle]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old("sections.{$key}.subtitle", $section['subtitle'] ?? '') }}</textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Offer text</label>
                    <input type="text" name="sections[{{ $key }}][offer]" value="{{ old("sections.{$key}.offer", $section['offer'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Offer code</label>
                    <input type="text" name="sections[{{ $key }}][offer_code]" value="{{ old("sections.{$key}.offer_code", $section['offer_code'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
            </div>
        @elseif ($key === HomepageSections::CTA)
            <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="sections[{{ $key }}][title]" value="{{ old("sections.{$key}.title", $section['title'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Subtitle</label>
                <textarea name="sections[{{ $key }}][subtitle]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old("sections.{$key}.subtitle", $section['subtitle'] ?? '') }}</textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Button text</label>
                    <input type="text" name="sections[{{ $key }}][button_text]" value="{{ old("sections.{$key}.button_text", $section['button_text'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Button URL</label>
                    <input type="text" name="sections[{{ $key }}][button_url]" value="{{ old("sections.{$key}.button_url", $section['button_url'] ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="{{ route('shop.contact') }}">
                    <p class="mt-1 text-xs text-gray-500">Leave blank to use the contact page.</p>
                </div>
            </div>
        @elseif ($key === HomepageSections::TRUST)
            @foreach (['why_choose' => 'Why choose us', 'shipping' => 'Shipping benefits', 'security' => 'Security badges', 'payments' => 'Payment methods'] as $blockKey => $blockLabel)
                <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                    <h4 class="font-medium text-gray-900">{{ $blockLabel }}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600">Badge</label>
                            <input type="text" name="sections[{{ $key }}][{{ $blockKey }}][badge]" value="{{ old("sections.{$key}.{$blockKey}.badge", $section[$blockKey]['badge'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">Title</label>
                            <input type="text" name="sections[{{ $key }}][{{ $blockKey }}][title]" value="{{ old("sections.{$key}.{$blockKey}.title", $section[$blockKey]['title'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">Subtitle</label>
                            <input type="text" name="sections[{{ $key }}][{{ $blockKey }}][subtitle]" value="{{ old("sections.{$key}.{$blockKey}.subtitle", $section[$blockKey]['subtitle'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>
                </div>
            @endforeach
            <p class="text-xs text-gray-500">Trust item cards and payment badges use ERP defaults. Edit section headings here; free shipping text still follows checkout shipping rules.</p>
        @endif
    </div>
</details>
