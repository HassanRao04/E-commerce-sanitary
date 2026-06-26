@extends('layouts.admin')

@section('title', $product->exists ? 'Edit Product' : 'Create Product')

@section('content')
    @php
        $currency = config('shop.currency_symbol');
        $existingVariants = old('variants', $product->variants?->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'variant_name' => $v->variant_name,
            'price' => $v->price,
            'sale_price' => $v->sale_price,
            'cost_price' => $v->cost_price,
            'stock_quantity' => $v->stock_quantity,
            'low_stock_threshold' => $v->low_stock_threshold,
            'is_default' => $v->is_default,
            'is_active' => $v->is_active,
            'attribute_values' => $v->attributeValues->map(fn ($av) => [
                'attribute_id' => $av->attribute_id,
                'attribute_value_id' => $av->attribute_value_id,
                'custom_value' => $av->custom_value,
            ])->values()->all(),
        ])->values()->all() ?? []);

        $existingProductAttributes = old('product_attributes', $product->attributeValues?->map(fn ($av) => [
            'attribute_id' => $av->attribute_id,
            'attribute_value_id' => $av->attribute_value_id,
            'custom_value' => $av->custom_value,
        ])->values()->all() ?? []);

        $alpineConfig = [
            'productType' => old('product_type', $product->product_type ?? 'simple'),
            'variants' => $existingVariants,
            'productAttributes' => $existingProductAttributes,
            'variantAttributes' => $variantAttributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])])->values(),
            'allAttributes' => $attributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])])->values(),
            'removedImages' => old('remove_image_ids', []),
        ];
    @endphp

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
                @foreach (['general' => 'General', 'pricing' => 'Pricing & Stock', 'variants' => 'Variants', 'images' => 'Images', 'attributes' => 'Attributes'] as $key => $label)
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
                    <x-input-label for="price" value="Price ({{ $currency }})" />
                    <x-text-input id="price" name="price" type="number" step="0.01" class="block mt-1 w-full" :value="old('price', $product->defaultVariant?->price ?? 0)" />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="sale_price" value="Sale Price" />
                    <x-text-input id="sale_price" name="sale_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('sale_price', $product->defaultVariant?->sale_price)" />
                </div>
                <div>
                    <x-input-label for="cost_price" value="Cost Price" />
                    <x-text-input id="cost_price" name="cost_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('cost_price', $product->defaultVariant?->cost_price)" />
                </div>
                <div>
                    <x-input-label for="stock_quantity" value="Stock Quantity" />
                    <x-text-input id="stock_quantity" name="stock_quantity" type="number" class="block mt-1 w-full" :value="old('stock_quantity', $product->defaultVariant?->stock_quantity ?? 0)" />
                    <x-input-error :messages="$errors->get('stock_quantity')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="low_stock_threshold" value="Low Stock Threshold" />
                    <x-text-input id="low_stock_threshold" name="low_stock_threshold" type="number" class="block mt-1 w-full" :value="old('low_stock_threshold', $product->defaultVariant?->low_stock_threshold ?? config('shop.low_stock_threshold'))" />
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">Stock syncs to the default warehouse inventory record automatically.</p>
        </div>

        {{-- Variants --}}
        <div x-show="tab === 'variants' || (tab === 'pricing' && productType === 'variable')" x-cloak class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold">Product Variants</h3>
                    <p class="text-sm text-gray-500">Manage SKU, pricing, stock, and variant attributes.</p>
                </div>
                <button type="button" @click="addVariant()" class="px-3 py-2 bg-slate-900 text-white text-sm rounded-md">Add Variant</button>
            </div>

            <template x-for="(variant, index) in variants" :key="index">
                <div class="border rounded-lg p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-medium text-sm" x-text="'Variant #' + (index + 1)"></h4>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1 text-xs">
                                <input type="radio" name="default_variant_index" :checked="variant.is_default" @change="setDefaultVariant(index)">
                                Default
                            </label>
                            <input type="hidden" :name="'variants['+index+'][is_default]'" :value="variant.is_default ? 1 : 0">
                            <button type="button" @click="removeVariant(index)" class="text-red-600 text-xs" x-show="variants.length > 1">Remove</button>
                        </div>
                    </div>
                    <input type="hidden" :name="'variants['+index+'][id]'" x-model="variant.id">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600">SKU</label>
                            <input type="text" :name="'variants['+index+'][sku]'" x-model="variant.sku" class="mt-1 w-full rounded-md border-gray-300 text-sm uppercase" required>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Name</label>
                            <input type="text" :name="'variants['+index+'][variant_name]'" x-model="variant.variant_name" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Price</label>
                            <input type="number" step="0.01" :name="'variants['+index+'][price]'" x-model="variant.price" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Sale Price</label>
                            <input type="number" step="0.01" :name="'variants['+index+'][sale_price]'" x-model="variant.sale_price" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Cost</label>
                            <input type="number" step="0.01" :name="'variants['+index+'][cost_price]'" x-model="variant.cost_price" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Stock</label>
                            <input type="number" :name="'variants['+index+'][stock_quantity]'" x-model="variant.stock_quantity" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Low Stock At</label>
                            <input type="number" :name="'variants['+index+'][low_stock_threshold]'" x-model="variant.low_stock_threshold" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm pb-2">
                                <input type="hidden" :name="'variants['+index+'][is_active]'" value="0">
                                <input type="checkbox" :name="'variants['+index+'][is_active]'" value="1" x-model="variant.is_active"> Active
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3" x-show="variantAttributes.length">
                        <template x-for="(attrRow, attrIndex) in variant.attribute_values" :key="attrIndex">
                            <div>
                                <label class="text-xs font-medium text-gray-600" x-text="variantAttributes[attrIndex]?.name"></label>
                                <input type="hidden" :name="'variants['+index+'][attribute_values]['+attrIndex+'][attribute_id]'" x-model="attrRow.attribute_id">
                                <select :name="'variants['+index+'][attribute_values]['+attrIndex+'][attribute_value_id]'" x-model="attrRow.attribute_value_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">— Select —</option>
                                    <template x-for="opt in variantAttributes[attrIndex]?.values || []" :key="opt.id">
                                        <option :value="opt.id" x-text="opt.value"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <x-input-error :messages="$errors->get('variants')" class="mt-2" />
            <x-input-error :messages="$errors->get('variants.*.sku')" class="mt-2" />
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

        <div class="flex gap-3">
            <x-primary-button>{{ $product->exists ? 'Save Product' : 'Create Product' }}</x-primary-button>
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
@endsection

@push('head')
    <style>[x-cloak]{display:none!important}</style>
@endpush
