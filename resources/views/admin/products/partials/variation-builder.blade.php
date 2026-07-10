{{-- Product Variation Builder --}}
<div class="variation-builder space-y-8">
    {{-- Attribute definition --}}
    <section class="variation-builder__section">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Variation Attributes</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Define attributes and values, then generate every combination automatically.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="addVariationAttribute()" class="variation-builder__btn variation-builder__btn--secondary">
                    + Add Attribute
                </button>
                <button type="button" @click="addPresetAttribute('Color')" class="variation-builder__btn variation-builder__btn--ghost">Color</button>
                <button type="button" @click="addPresetAttribute('Size')" class="variation-builder__btn variation-builder__btn--ghost">Size</button>
                <button type="button" @click="addPresetAttribute('Material')" class="variation-builder__btn variation-builder__btn--ghost">Material</button>
            </div>
        </div>

        <div class="mt-6 space-y-4" x-show="variationAttributes.length === 0">
            <div class="variation-builder__empty">
                <p>No attributes yet. Add Color, Size, Material, or a custom attribute to get started.</p>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            <template x-for="(attribute, attrIndex) in variationAttributes" :key="attribute._key">
                <div class="variation-builder__attribute-card">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1">
                            <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Attribute name</label>
                            <input
                                type="text"
                                x-model="attribute.name"
                                @input="attribute.slug = slugify(attribute.name)"
                                placeholder="e.g. Color, Size, Material"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <button type="button" @click="removeVariationAttribute(attrIndex)" class="text-sm text-red-600 hover:text-red-700 sm:mt-6">
                            Remove
                        </button>
                    </div>

                    <div class="mt-4">
                        <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Values</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="(value, valueIndex) in attribute.values" :key="valueIndex">
                                <span class="variation-builder__chip" :class="{ 'variation-builder__chip--color': isColorAttribute(attribute) }">
                                    <span
                                        x-show="isColorAttribute(attribute) && valueHex(value)"
                                        class="variation-builder__swatch"
                                        :style="`background-color: ${valueHex(value)}`"
                                    ></span>
                                    <span x-text="valueLabel(value)"></span>
                                    <span x-show="isColorAttribute(attribute) && valueHex(value)" class="text-xs text-gray-400" x-text="valueHex(value)"></span>
                                    <button type="button" @click="removeAttributeValue(attrIndex, valueIndex)" aria-label="Remove value">&times;</button>
                                </span>
                            </template>
                        </div>

                        <template x-if="isColorAttribute(attribute)">
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-gray-500">Color name</label>
                                    <input
                                        type="text"
                                        x-model="attribute.newValue"
                                        @keydown.enter.prevent="addColorAttributeValue(attrIndex)"
                                        placeholder="e.g. Black, White, Gold"
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Hex code</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input
                                            type="color"
                                            x-model="attribute.newHex"
                                            class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1"
                                            aria-label="Pick color"
                                        >
                                        <input
                                            type="text"
                                            x-model="attribute.newHex"
                                            @input="attribute.newHex = normalizeHexInput(attribute.newHex)"
                                            placeholder="#000000"
                                            maxlength="7"
                                            class="w-28 rounded-lg border-gray-300 text-sm uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>
                                </div>
                                <button type="button" @click="addColorAttributeValue(attrIndex)" class="variation-builder__btn variation-builder__btn--secondary">
                                    Add Color
                                </button>
                            </div>
                        </template>

                        <template x-if="!isColorAttribute(attribute)">
                            <div class="mt-3 flex gap-2">
                                <input
                                    type="text"
                                    x-model="attribute.newValue"
                                    @keydown.enter.prevent="addAttributeValue(attrIndex)"
                                    placeholder="Add a value and press Enter"
                                    class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <button type="button" @click="addAttributeValue(attrIndex)" class="variation-builder__btn variation-builder__btn--secondary">
                                    Add
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-600">
                <span x-text="expectedCombinationCount()"></span> possible combination(s)
            </p>
            <button
                type="button"
                @click="generateVariations()"
                :disabled="!canGenerateVariations()"
                class="variation-builder__btn variation-builder__btn--primary"
                :class="{ 'opacity-50 cursor-not-allowed': !canGenerateVariations() }"
            >
                Generate Variations
            </button>
        </div>
    </section>

    {{-- Generated variations table --}}
    <section class="variation-builder__section">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900">
                    Variations
                    <span class="text-gray-400 font-normal" x-show="variants.length" x-text="'(' + variants.length + ')'"></span>
                </h3>
                <p class="mt-1 text-sm text-gray-500">Set SKU, pricing, stock, status, and image for each variation.</p>
            </div>
            <button type="button" @click="addVariant()" class="variation-builder__btn variation-builder__btn--secondary">
                Add Manual Variation
            </button>
        </div>

        <div class="mt-4 variation-builder__empty" x-show="variants.length === 0">
            <p>Generate variations from attributes above, or add a manual variation row.</p>
        </div>

        <div class="mt-4 overflow-x-auto" x-show="variants.length > 0">
            <table class="variation-builder__table min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="variation-builder__th">Combination</th>
                        <th class="variation-builder__th">SKU</th>
                        <th class="variation-builder__th">Base</th>
                        <th class="variation-builder__th">Sale</th>
                        <th class="variation-builder__th">Wholesale</th>
                        <th class="variation-builder__th">Dealer</th>
                        <th class="variation-builder__th">Stock</th>
                        <th class="variation-builder__th">Status</th>
                        <th class="variation-builder__th">Image</th>
                        <th class="variation-builder__th"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-for="(variant, index) in variants" :key="variant._key || index">
                        <tr class="variation-builder__row">
                            <td class="variation-builder__td">
                                <input type="hidden" :name="'variants['+index+'][id]'" x-model="variant.id">
                                <input type="hidden" :name="'variants['+index+'][is_default]'" :value="variant.is_default ? 1 : 0">
                                <input type="hidden" :name="'variants['+index+'][variant_name]'" :value="variantLabel(variant)">
                                <input type="hidden" :name="'variants['+index+'][cost_price]'" x-model="variant.cost_price">
                                <input type="hidden" :name="'variants['+index+'][low_stock_threshold]'" x-model="variant.low_stock_threshold">
                                <template x-for="(attrRow, attrIndex) in variant.attribute_values" :key="attrIndex">
                                    <input type="hidden" :name="'variants['+index+'][attribute_values]['+attrIndex+'][attribute_id]'" :value="attrRow.attribute_id || ''">
                                    <input type="hidden" :name="'variants['+index+'][attribute_values]['+attrIndex+'][attribute_name]'" :value="attrRow.attribute_name || ''">
                                    <input type="hidden" :name="'variants['+index+'][attribute_values]['+attrIndex+'][attribute_slug]'" :value="attrRow.attribute_slug || ''">
                                    <input type="hidden" :name="'variants['+index+'][attribute_values]['+attrIndex+'][attribute_value_id]'" :value="attrRow.attribute_value_id || ''">
                                    <input type="hidden" :name="'variants['+index+'][attribute_values]['+attrIndex+'][value]'" :value="attrRow.value || ''">
                                    <input type="hidden" :name="'variants['+index+'][attribute_values]['+attrIndex+'][color_hex]'" :value="attrRow.color_hex || ''">
                                </template>
                                <div class="font-medium text-sm text-gray-900" x-text="variantLabel(variant)"></div>
                                <label class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                    <input type="radio" name="default_variant_index" :checked="variant.is_default" @change="setDefaultVariant(index)">
                                    Default
                                </label>
                            </td>
                            <td class="variation-builder__td">
                                <input type="text" :name="'variants['+index+'][sku]'" x-model="variant.sku" required class="variation-builder__input uppercase" placeholder="SKU">
                            </td>
                            <td class="variation-builder__td">
                                <input type="number" step="0.01" :name="'variants['+index+'][price]'" x-model="variant.price" required class="variation-builder__input" placeholder="0.00">
                            </td>
                            <td class="variation-builder__td">
                                <input type="number" step="0.01" :name="'variants['+index+'][sale_price]'" x-model="variant.sale_price" class="variation-builder__input" placeholder="—">
                            </td>
                            <td class="variation-builder__td">
                                <input type="number" step="0.01" :name="'variants['+index+'][wholesale_price]'" x-model="variant.wholesale_price" class="variation-builder__input" placeholder="—">
                            </td>
                            <td class="variation-builder__td">
                                <input type="number" step="0.01" :name="'variants['+index+'][dealer_price]'" x-model="variant.dealer_price" class="variation-builder__input" placeholder="—">
                            </td>
                            <td class="variation-builder__td">
                                <input type="number" :name="'variants['+index+'][stock_quantity]'" x-model="variant.stock_quantity" required class="variation-builder__input" min="0">
                            </td>
                            <td class="variation-builder__td">
                                <input type="hidden" :name="'variants['+index+'][is_active]'" value="0">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" :name="'variants['+index+'][is_active]'" value="1" x-model="variant.is_active" class="rounded border-gray-300 text-indigo-600">
                                    <span x-text="variant.is_active ? 'Active' : 'Inactive'"></span>
                                </label>
                            </td>
                            <td class="variation-builder__td">
                                <div class="flex items-center gap-2">
                                    <div class="variation-builder__thumb" x-show="variantImagePreview(variant)">
                                        <img :src="variantImagePreview(variant)" alt="" class="h-10 w-10 rounded object-cover ring-1 ring-gray-200">
                                    </div>
                                    <input
                                        type="file"
                                        :name="'variants['+index+'][image]'"
                                        accept="image/jpeg,image/png,image/webp"
                                        @change="previewVariantImage($event, index)"
                                        class="block w-full max-w-[9rem] text-xs text-gray-500 file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-xs file:font-medium"
                                    >
                                    <input type="hidden" :name="'variants['+index+'][remove_image]'" :value="variant.remove_image ? 1 : 0">
                                    <button
                                        type="button"
                                        x-show="variant.image_url || variant.image_preview"
                                        @click="clearVariantImage(index)"
                                        class="text-xs text-red-600 whitespace-nowrap"
                                    >Remove</button>
                                </div>
                            </td>
                            <td class="variation-builder__td text-right">
                                <button type="button" @click="removeVariant(index)" class="text-xs text-red-600 hover:text-red-700" x-show="variants.length > 1">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <x-input-error :messages="$errors->get('variants')" class="mt-4" />
        <x-input-error :messages="$errors->get('variants.*.sku')" class="mt-2" />
        <x-input-error :messages="$errors->get('variants.*.price')" class="mt-2" />
        <x-input-error :messages="$errors->get('variants.*.image')" class="mt-2" />
    </section>
</div>
