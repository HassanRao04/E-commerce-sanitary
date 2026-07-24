@extends('layouts.admin')

@section('title', 'Shipping Settings')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Shipping Settings'])

    <p class="text-sm text-gray-500 mb-6">Configure delivery charges, free shipping rules, and product or category rates used at checkout.</p>

    <form
        method="POST"
        action="{{ route('admin.shipping.settings.update') }}"
        class="space-y-6"
        x-data="shippingSettingsForm(@js([
            'productRates' => old('product_rates', $productRates->map(fn ($rate) => [
                'product_id' => $rate->product_id,
                'product_label' => $rate->product?->name.' ('.$rate->product?->base_sku.')',
                'amount' => (float) $rate->amount,
                'free_shipping' => (bool) $rate->free_shipping,
                'is_active' => $rate->is_active,
            ])->values()->all()),
            'categoryRates' => old('category_rates', $categoryRates->map(fn ($rate) => [
                'category_id' => $rate->category_id,
                'amount' => (float) $rate->amount,
                'is_active' => $rate->is_active,
            ])->values()->all()),
            'searchUrl' => route('admin.shipping.settings.products.search'),
        ]))"
    >
        @csrf
        @method('PATCH')

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-5">
            <h3 class="font-semibold text-gray-900">Shipping methods</h3>
            <p class="text-sm text-gray-500">Checkout always uses the default method below. Saving activates that method only (Flat, Product-based, or Category-based). Free shipping remains a separate rule.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                    <input type="checkbox" name="flat_rate_enabled" value="1" class="mt-1" @checked(old('flat_rate_enabled', $settings->flat_rate_enabled))>
                    <span>
                        <span class="font-medium text-gray-900">Flat rate shipping</span>
                        <span class="block text-sm text-gray-500">Single delivery charge for the whole order.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                    <input type="checkbox" name="product_rate_enabled" value="1" class="mt-1" @checked(old('product_rate_enabled', $settings->product_rate_enabled))>
                    <span>
                        <span class="font-medium text-gray-900">Product-based shipping</span>
                        <span class="block text-sm text-gray-500">Charge per product using rates defined below.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                    <input type="checkbox" name="category_rate_enabled" value="1" class="mt-1" @checked(old('category_rate_enabled', $settings->category_rate_enabled))>
                    <span>
                        <span class="font-medium text-gray-900">Category-based shipping</span>
                        <span class="block text-sm text-gray-500">Charge based on each product&apos;s category.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                    <input type="checkbox" name="free_shipping_enabled" value="1" class="mt-1" @checked(old('free_shipping_enabled', $settings->free_shipping_enabled))>
                    <span>
                        <span class="font-medium text-gray-900">Free shipping rules</span>
                        <span class="block text-sm text-gray-500">Waive delivery when the order total exceeds a threshold.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Default shipping method</label>
                <select name="default_method" class="mt-1 w-full max-w-md rounded-md border-gray-300 shadow-sm text-sm" required>
                    @foreach (\App\Enums\ShippingMethod::cases() as $method)
                        <option value="{{ $method->value }}" @selected(old('default_method', $settings->default_method?->value) === $method->value)>
                            {{ $method->label() }}
                        </option>
                    @endforeach
                </select>
                @error('default_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Flat rate</h3>
            <div class="max-w-xs">
                <label class="block text-sm font-medium text-gray-700">Delivery charge</label>
                <input type="number" step="0.01" min="0" name="flat_rate_amount" value="{{ old('flat_rate_amount', $settings->flat_rate_amount) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                <p class="mt-1 text-xs text-gray-500">Example: 300 — one delivery charge for the entire order.</p>
                @error('flat_rate_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <h3 class="font-semibold text-gray-900">Free shipping</h3>
            <div class="max-w-xs">
                <label class="block text-sm font-medium text-gray-700">Free shipping above</label>
                <input type="number" step="0.01" min="0" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings->free_shipping_threshold) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            <p class="mt-1 text-xs text-gray-500">Example: 10000 — orders at or above this amount ship free. Below the threshold, checkout uses the default shipping method. When free shipping is disabled, the default method always applies.</p>
                @error('free_shipping_threshold')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Product shipping rates</h3>
                    <p class="text-sm text-gray-500">Example: Mirror = 200, Commode = 600. Mark Free Shipping for products that always ship free; if every cart item has Free Shipping, the order ships free.</p>
                </div>
                <button type="button" class="px-3 py-2 text-sm border rounded-md hover:bg-gray-50" @click="addProductRate()">Add product rate</button>
            </div>

            <template x-if="productRates.length === 0">
                <p class="text-sm text-gray-500">No product-specific rates configured yet.</p>
            </template>

            <div class="space-y-3">
                <template x-for="(rate, index) in productRates" :key="index">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end border border-gray-100 rounded-lg p-4">
                        <div class="md:col-span-5">
                            <label class="block text-sm font-medium text-gray-700">Product</label>
                            <input type="hidden" :name="`product_rates[${index}][product_id]`" x-model="rate.product_id">
                            <div class="mt-1 flex gap-2">
                                <input type="text" x-model="rate.product_label" placeholder="Search product..." class="w-full rounded-md border-gray-300 shadow-sm text-sm" @input.debounce.300ms="searchProduct(index)">
                            </div>
                            <div class="relative">
                                <ul x-show="rate.results?.length" x-cloak class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg max-h-48 overflow-y-auto">
                                    <template x-for="product in rate.results" :key="product.id">
                                        <li>
                                            <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50" @click="selectProduct(index, product)" x-text="product.label"></button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Charge</label>
                            <input type="number" step="0.01" min="0" :name="`product_rates[${index}][amount]`" x-model="rate.amount" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2 text-sm mt-6">
                                <input type="checkbox" :name="`product_rates[${index}][free_shipping]`" value="1" x-model="rate.free_shipping">
                                Free shipping
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2 text-sm mt-6">
                                <input type="checkbox" :name="`product_rates[${index}][is_active]`" value="1" x-model="rate.is_active">
                                Active
                            </label>
                        </div>
                        <div class="md:col-span-1">
                            <button type="button" class="text-sm text-red-600 hover:underline" @click="removeProductRate(index)">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Category shipping rates</h3>
                    <p class="text-sm text-gray-500">Example: Bathroom Accessories, Kitchen Accessories, Sanitary Ware — each category can have its own charge.</p>
                </div>
                <button type="button" class="px-3 py-2 text-sm border rounded-md hover:bg-gray-50" @click="addCategoryRate()">Add category rate</button>
            </div>

            <template x-if="categoryRates.length === 0">
                <p class="text-sm text-gray-500">No category-specific rates configured yet.</p>
            </template>

            <div class="space-y-3">
                <template x-for="(rate, index) in categoryRates" :key="index">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end border border-gray-100 rounded-lg p-4">
                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select :name="`category_rates[${index}][category_id]`" x-model="rate.category_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Charge</label>
                            <input type="number" step="0.01" min="0" :name="`category_rates[${index}][amount]`" x-model="rate.amount" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2 text-sm mt-6">
                                <input type="checkbox" :name="`category_rates[${index}][is_active]`" value="1" x-model="rate.is_active">
                                Active
                            </label>
                        </div>
                        <div class="md:col-span-1">
                            <button type="button" class="text-sm text-red-600 hover:underline" @click="removeCategoryRate(index)">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        @can('update', $settings)
            <x-primary-button>Save shipping settings</x-primary-button>
        @endcan
    </form>
@endsection

@push('scripts')
<script>
function shippingSettingsForm(config) {
    return {
        productRates: config.productRates ?? [],
        categoryRates: config.categoryRates ?? [],
        searchUrl: config.searchUrl,

        addProductRate() {
            this.productRates.push({ product_id: '', product_label: '', amount: '', free_shipping: false, is_active: true, results: [] });
        },

        removeProductRate(index) {
            this.productRates.splice(index, 1);
        },

        addCategoryRate() {
            this.categoryRates.push({ category_id: '', amount: '', is_active: true });
        },

        removeCategoryRate(index) {
            this.categoryRates.splice(index, 1);
        },

        async searchProduct(index) {
            const rate = this.productRates[index];
            if (!rate.product_label || rate.product_label.length < 2) {
                rate.results = [];
                return;
            }

            const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(rate.product_label)}`, {
                headers: { Accept: 'application/json' },
            });

            const data = await response.json();
            rate.results = data.products ?? [];
        },

        selectProduct(index, product) {
            const rate = this.productRates[index];
            rate.product_id = product.id;
            rate.product_label = product.label;
            rate.results = [];
        },
    };
}
</script>
@endpush
