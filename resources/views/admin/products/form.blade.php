@extends('layouts.admin')

@section('title', $product->exists ? 'Edit Product' : 'Create Product')

@section('content')
    @php
        $existingVariants = old('variants', $existingVariants ?? []);
        $existingVariationAttributes = old('variation_attributes', $variationAttributes ?? []);

        $existingProductAttributes = old('product_attributes', $product->attributeValues?->map(fn ($av) => [
            'attribute_id' => $av->attribute_id,
            'attribute_value_id' => $av->attribute_value_id,
            'custom_value' => $av->custom_value,
        ])->values()->all() ?? []);

        $existingOfferTiers = old('offer_tiers', $product->offers?->map(fn ($offer) => [
            'buy_quantity' => $offer->buy_quantity,
            'discount_percent' => (float) $offer->discount_percent,
            'free_shipping' => (bool) $offer->free_shipping,
        ])->values()->all() ?? []);

        $existingPipeLengthOptions = old('pipe_length_options', $product->pipeLengthOptions?->map(fn ($option) => [
            'label' => $option->label,
            'additional_price' => (float) $option->additional_price,
        ])->values()->all() ?? []);

        $alpineConfig = [
            'productType' => old('product_type', $product->product_type ?? 'simple'),
            'variants' => $existingVariants,
            'variationAttributes' => $existingVariationAttributes,
            'productAttributes' => $existingProductAttributes,
            'offersEnabled' => (bool) old('offers_enabled', $product->offers_enabled ?? false),
            'offerTiers' => $existingOfferTiers,
            'pipeLengthEnabled' => (bool) old('pipe_length_enabled', $product->pipe_length_enabled ?? false),
            'optionTitle' => old('option_title', $product->option_title ?? ''),
            'pipeLengthOptions' => $existingPipeLengthOptions,
            'variantAttributes' => $variantAttributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])])->values(),
            'allAttributes' => $attributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])])->values(),
            'removedImages' => old('remove_image_ids', []),
            'defaultPrice' => old('price', $product->defaultVariant?->price ?? ''),
            'defaultSalePrice' => old('sale_price', $product->defaultVariant?->sale_price ?? ''),
            'defaultWholesalePrice' => old('wholesale_price', $product->defaultVariant?->wholesale_price ?? ''),
            'defaultDealerPrice' => old('dealer_price', $product->defaultVariant?->dealer_price ?? ''),
        ];
    @endphp
    @php $currency = config('shop.currency_symbol'); @endphp

    @include('admin.partials.page-header', [
        'title' => $product->exists ? 'Edit Product' : 'Create Product',
    ])

    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
        class="space-y-6"
        x-data="productForm(@js($alpineConfig))"
    >
        @csrf
        @if ($product->exists) @method('PUT') @endif

        {{-- Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex flex-wrap gap-4">
                @foreach (['general' => 'General', 'pricing' => 'Pricing & Stock', 'variants' => 'Variations', 'images' => 'Images', 'attributes' => 'Specifications', 'offers' => 'Offers', 'pipe' => 'Options'] as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="border-b-2 px-1 py-3 text-sm font-medium">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- General --}}
        <div x-show="tab === 'general'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="name" value="Product Name" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $product->name)" required @input="slugifyName()" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" value="Slug" />
                    <x-text-input id="slug" name="slug" class="block mt-1 w-full" :value="old('slug', $product->slug)" required @input="markSlugManual()" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="base_sku" value="Base SKU" />
                    <x-text-input id="base_sku" name="base_sku" class="block mt-1 w-full uppercase" :value="old('base_sku', $product->base_sku)" required />
                    <x-input-error :messages="$errors->get('base_sku')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="brand_id" value="Brand" />
                    <select id="brand_id" name="brand_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">— None —</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                        @foreach (\App\Enums\ProductStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $product->status?->value ?? 'draft') === $status->value)>{{ ucfirst($status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="product_type" value="Product Type" />
                    <select id="product_type" name="product_type" x-model="productType" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="simple">Simple (single SKU)</option>
                        <option value="variable">Variable (multiple variants)</option>
                    </select>
                </div>
            </div>

            <div>
                <x-input-label value="Categories" />
                <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border rounded-md p-3">
                    @php $selected = old('category_ids', $product->categories?->pluck('id')->all() ?? []); @endphp
                    @foreach ($categories as $category)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $selected))>
                            @if ($category->parent)<span class="text-gray-400">{{ $category->parent->name }} ›</span>@endif
                            {{ $category->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="short_description" value="Short Description" />
                    <textarea id="short_description" name="short_description" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('short_description', $product->short_description) }}</textarea>
                </div>
                <div>
                    <x-input-label for="warranty_text" value="Warranty" />
                    <textarea id="warranty_text" name="warranty_text" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('warranty_text', $product->warranty_text) }}</textarea>
                </div>
            </div>

            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="5" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> Featured</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_new_arrival" value="1" @checked(old('is_new_arrival', $product->is_new_arrival))> New Arrival</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_best_seller" value="1" @checked(old('is_best_seller', $product->is_best_seller))> Best Seller</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_project_suitable" value="1" @checked(old('is_project_suitable', $product->is_project_suitable))> Project Suitable</label>
            </div>
        </div>

        {{-- Simple pricing --}}
        <div x-show="tab === 'pricing' && productType === 'simple'" x-cloak class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
            <h3 class="text-base font-semibold mb-4">Pricing & Inventory</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <x-input-label for="price" value="Base Price ({{ $currency }})" />
                    <x-text-input id="price" name="price" type="number" step="0.01" class="block mt-1 w-full" :value="old('price', $product->defaultVariant?->price ?? 0)" />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="sale_price" value="Sale Price" />
                    <x-text-input id="sale_price" name="sale_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('sale_price', $product->defaultVariant?->sale_price)" />
                </div>
                <div>
                    <x-input-label for="wholesale_price" value="Wholesale Price" />
                    <x-text-input id="wholesale_price" name="wholesale_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('wholesale_price', $product->defaultVariant?->wholesale_price)" />
                </div>
                <div>
                    <x-input-label for="dealer_price" value="Dealer Price" />
                    <x-text-input id="dealer_price" name="dealer_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('dealer_price', $product->defaultVariant?->dealer_price)" />
                </div>
                <div>
                    <x-input-label for="cost_price" value="Cost Price" />
                    <x-text-input id="cost_price" name="cost_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('cost_price', $product->defaultVariant?->cost_price)" />
                </div>
                <div>
                    <x-input-label for="stock_quantity" value="On-Hand Stock (ERP)" />
                    <x-text-input id="stock_quantity" name="stock_quantity" type="number" class="block mt-1 w-full" :value="old('stock_quantity', $product->defaultVariant?->stock_quantity ?? 0)" />
                    <p class="mt-1 text-xs text-gray-500">Website availability is calculated from ERP warehouse stock minus active cart reservations.</p>
                    <x-input-error :messages="$errors->get('stock_quantity')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="low_stock_threshold" value="Low Stock Threshold" />
                    <x-text-input id="low_stock_threshold" name="low_stock_threshold" type="number" class="block mt-1 w-full" :value="old('low_stock_threshold', $product->defaultVariant?->low_stock_threshold ?? config('shop.low_stock_threshold'))" />
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">Stock syncs to the default warehouse inventory record automatically.</p>
        </div>

        {{-- Variations (variable products) --}}
        <div x-show="(tab === 'variants' || (tab === 'pricing' && productType === 'variable')) && productType === 'variable'" x-cloak class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
            @include('admin.products.partials.variation-builder')
        </div>

        {{-- Images --}}
        <div x-show="tab === 'images'" x-cloak class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-6">
            <div>
                <x-input-label for="images" value="Upload Images" />
                <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp" @change="previewImages($event)" class="mt-1 block w-full text-sm">
                <p class="mt-1 text-xs text-gray-500">Up to 10 images, 5 MB each (JPEG, PNG, WebP).</p>
                <x-input-error :messages="$errors->get('images')" class="mt-2" />
                <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
            </div>

            <div x-show="imagePreviews.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <template x-for="preview in imagePreviews" :key="preview.name">
                    <div class="border rounded-lg overflow-hidden">
                        <img :src="preview.url" :alt="preview.name" class="h-32 w-full object-cover">
                        <p class="p-2 text-xs truncate" x-text="preview.name"></p>
                    </div>
                </template>
            </div>

            @if ($product->exists && $product->images->isNotEmpty())
                <div>
                    <h4 class="text-sm font-semibold mb-3">Existing Images</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach ($product->images->sortBy('sort_order') as $image)
                            <div class="relative border rounded-lg overflow-hidden" x-show="!isImageRemoved({{ $image->id }})">
                                <img src="{{ $image->url }}" alt="{{ $image->alt_text }}" class="h-32 w-full object-cover">
                                <div class="p-2 flex items-center justify-between gap-2 text-xs">
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked($image->is_primary)>
                                        Primary
                                    </label>
                                    <button type="button" @click="markImageRemoved({{ $image->id }})" class="text-red-600">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <template x-for="id in removedImages" :key="id">
                        <input type="hidden" name="remove_image_ids[]" :value="id">
                    </template>
                </div>
            @endif
        </div>

        {{-- Product attributes --}}
        <div x-show="tab === 'attributes'" x-cloak class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold">Product Attributes</h3>
                    <p class="text-sm text-gray-500">Specification attributes shown on the product page.</p>
                </div>
                <button type="button" @click="addProductAttribute()" class="px-3 py-2 bg-slate-900 text-white text-sm rounded-md">Add Attribute</button>
            </div>

            <template x-for="(row, index) in productAttributes" :key="index">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end border rounded-lg p-4">
                    <div>
                        <label class="text-xs font-medium text-gray-600">Attribute</label>
                        <select :name="'product_attributes['+index+'][attribute_id]'" x-model="row.attribute_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">— Select —</option>
                            @foreach ($attributes->where('is_variant_attribute', false) as $attribute)
                                <option value="{{ $attribute->id }}">{{ $attribute->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Value</label>
                        <select :name="'product_attributes['+index+'][attribute_value_id]'" x-model="row.attribute_value_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option value="">— Select —</option>
                            <template x-for="opt in attributeOptions(row.attribute_id)" :key="opt.id">
                                <option :value="opt.id" x-text="opt.value"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-xs font-medium text-gray-600">Or Custom</label>
                            <input type="text" :name="'product_attributes['+index+'][custom_value]'" x-model="row.custom_value" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <button type="button" @click="removeProductAttribute(index)" class="text-red-600 text-sm pb-2">Remove</button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Offers --}}
        <div x-show="tab === 'offers'" x-cloak class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-5">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Offer configuration</h3>
                <p class="mt-1 text-sm text-gray-500">Enable product offers and define quantity tiers (for example Buy 2 = 10% off, Buy 3 = 15% + free shipping).</p>
            </div>

            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="hidden" name="offers_enabled" value="0">
                <input type="checkbox" name="offers_enabled" value="1" class="mt-1" x-model="offersEnabled">
                <span>
                    <span class="font-medium text-gray-900">Enable product offers</span>
                    <span class="block text-sm text-gray-500">When disabled, this product sells at normal price with no offer tiers applied.</span>
                </span>
            </label>

            <div x-show="offersEnabled" x-cloak class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm text-gray-600">Offer tiers</p>
                    <button type="button" @click="addOfferTier()" class="px-3 py-2 text-sm border rounded-md hover:bg-gray-50">Add tier</button>
                </div>

                <template x-if="offerTiers.length === 0">
                    <p class="text-sm text-gray-500">No tiers yet. Add a tier such as Buy 2 / 10% discount.</p>
                </template>

                <div class="space-y-3">
                    <template x-for="(tier, index) in offerTiers" :key="tier._key">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end border border-gray-100 rounded-lg p-4">
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Buy quantity</label>
                                <input type="number" min="1" step="1" :name="`offer_tiers[${index}][buy_quantity]`" x-model="tier.buy_quantity" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="2">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Discount %</label>
                                <input type="number" min="0" max="100" step="0.01" :name="`offer_tiers[${index}][discount_percent]`" x-model="tier.discount_percent" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="10">
                            </div>
                            <div class="md:col-span-4">
                                <label class="flex items-center gap-2 text-sm mt-6">
                                    <input type="checkbox" :name="`offer_tiers[${index}][free_shipping]`" value="1" x-model="tier.free_shipping">
                                    Free shipping
                                </label>
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" class="text-sm text-red-600 hover:underline" @click="removeOfferTier(index)">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                @error('offer_tiers')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('offer_tiers.*.buy_quantity')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Configurable product options (priced add-ons) --}}
        <div x-show="tab === 'pipe'" x-cloak class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-5">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Product options</h3>
                <p class="mt-1 text-sm text-gray-500">Optional add-ons with an additional price. Set any option title — the storefront will show it automatically.</p>
            </div>

            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="hidden" name="pipe_length_enabled" value="0">
                <input type="checkbox" name="pipe_length_enabled" value="1" class="mt-1" x-model="pipeLengthEnabled">
                <span>
                    <span class="font-medium text-gray-900">Enable product options</span>
                    <span class="block text-sm text-gray-500">When disabled, customers will not see this option selector for this product.</span>
                </span>
            </label>

            <div x-show="pipeLengthEnabled" x-cloak class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="option_title">Option title</label>
                    <input
                        type="text"
                        id="option_title"
                        name="option_title"
                        x-model="optionTitle"
                        class="mt-1 w-full max-w-md rounded-md border-gray-300 shadow-sm text-sm"
                        placeholder="e.g. Size, Hose Length, Warranty, Installation Kit"
                        maxlength="100"
                    >
                    <p class="mt-1 text-xs text-gray-500">This label appears on the product page, cart, and checkout.</p>
                    @error('option_title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm text-gray-600">Option values</p>
                    <button type="button" @click="addPipeLengthOption()" class="px-3 py-2 text-sm border rounded-md hover:bg-gray-50">Add option</button>
                </div>

                <template x-if="pipeLengthOptions.length === 0">
                    <p class="text-sm text-gray-500">No options yet. Example: Small (+0), Large (+250).</p>
                </template>

                <div class="space-y-3">
                    <template x-for="(option, index) in pipeLengthOptions" :key="option._key">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end border border-gray-100 rounded-lg p-4">
                            <div class="md:col-span-5">
                                <label class="block text-sm font-medium text-gray-700">Label</label>
                                <input type="text" :name="`pipe_length_options[${index}][label]`" x-model="option.label" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Large">
                            </div>
                            <div class="md:col-span-5">
                                <label class="block text-sm font-medium text-gray-700">Additional price ({{ $currency }})</label>
                                <input type="number" min="0" step="0.01" :name="`pipe_length_options[${index}][additional_price]`" x-model="option.additional_price" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="250">
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" class="text-sm text-red-600 hover:underline" @click="removePipeLengthOption(index)">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                @error('pipe_length_options')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('pipe_length_options.*.label')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex gap-3">
            <x-primary-button>{{ $product->exists ? 'Save Product' : 'Create Product' }}</x-primary-button>
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
@endsection

@push('head')
    <style>[x-cloak]{display:none!important}</style>
@endpush
