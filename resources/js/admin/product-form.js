import Alpine from 'alpinejs';

let keyCounter = 0;

const DEFAULT_COLOR_PALETTE = {
    Black: '#000000',
    White: '#FFFFFF',
    Gold: '#D4AF37',
};

function nextKey(prefix = 'row') {
    keyCounter += 1;
    return `${prefix}-${keyCounter}`;
}

function slugify(value) {
    return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
}

function normalizeAttributeValue(raw) {
    if (typeof raw === 'object' && raw !== null && !Array.isArray(raw)) {
        return {
            value: raw.value || '',
            hex: normalizeHexInput(raw.hex || ''),
        };
    }

    return { value: String(raw || ''), hex: '' };
}

function valueLabel(entry) {
    return normalizeAttributeValue(entry).value;
}

function valueHex(entry) {
    return normalizeAttributeValue(entry).hex;
}

function isColorAttribute(attribute) {
    const slug = (attribute.slug || slugify(attribute.name || '')).toLowerCase();

    return slug === 'color' || (attribute.name || '').toLowerCase() === 'color';
}

function normalizeHexInput(hex) {
    if (!hex) {
        return '';
    }

    let normalized = String(hex).trim().toUpperCase();

    if (!normalized.startsWith('#')) {
        normalized = `#${normalized}`;
    }

    if (/^#[0-9A-F]{6}$/.test(normalized)) {
        return normalized;
    }

    return hex;
}

function colorValueKey(entry) {
    return valueLabel(entry).toLowerCase();
}

function cartesian(arrays) {
    if (!arrays.length) {
        return [[]];
    }

    return arrays.reduce(
        (acc, current) => acc.flatMap((prefix) => current.map((item) => [...prefix, item])),
        [[]],
    );
}

document.addEventListener('alpine:init', () => {
    Alpine.data('productForm', (config = {}) => ({
        tab: 'general',
        productType: config.productType || 'simple',
        variants: (config.variants || []).map((variant) => ({
            ...variant,
            _key: variant._key || nextKey('variant'),
            remove_image: Boolean(variant.remove_image),
            is_active: variant.is_active !== false && variant.is_active !== 0 && variant.is_active !== '0',
        })),
        variationAttributes: (config.variationAttributes || []).map((attribute) => ({
            id: attribute.id || null,
            name: attribute.name || '',
            slug: attribute.slug || slugify(attribute.name),
            values: (attribute.values || []).map((entry) => normalizeAttributeValue(entry)),
            newValue: '',
            newHex: attribute.newHex || '#000000',
            _key: attribute._key || nextKey('attr'),
        })),
        productAttributes: config.productAttributes || [],
        offersEnabled: Boolean(config.offersEnabled),
        offerTiers: (config.offerTiers || []).map((tier) => ({
            buy_quantity: tier.buy_quantity ?? '',
            discount_percent: tier.discount_percent ?? '',
            free_shipping: Boolean(tier.free_shipping),
            _key: tier._key || nextKey('offer'),
        })),
        pipeLengthEnabled: Boolean(config.pipeLengthEnabled),
        optionTitle: config.optionTitle ?? '',
        pipeLengthOptions: (config.pipeLengthOptions || []).map((option) => ({
            label: option.label ?? '',
            additional_price: option.additional_price ?? '',
            _key: option._key || nextKey('pipe'),
        })),
        variantAttributes: config.variantAttributes || [],
        imagePreviews: [],
        removedImages: config.removedImages || [],
        defaultPrice: config.defaultPrice ?? '',
        defaultSalePrice: config.defaultSalePrice ?? '',
        defaultWholesalePrice: config.defaultWholesalePrice ?? '',
        defaultDealerPrice: config.defaultDealerPrice ?? '',

        init() {
            if (this.productType === 'variable' && this.variants.length === 0) {
                this.rebuildVariationAttributesFromVariants();
            }

            this.$watch('productType', (value) => {
                if (value === 'variable' && this.variants.length === 0) {
                    this.rebuildVariationAttributesFromVariants();
                }
            });
        },

        slugify,
        isColorAttribute,
        valueLabel,
        valueHex,
        normalizeHexInput,

        addOfferTier() {
            this.offerTiers.push({
                buy_quantity: '',
                discount_percent: '',
                free_shipping: false,
                _key: nextKey('offer'),
            });
        },

        removeOfferTier(index) {
            this.offerTiers.splice(index, 1);
        },

        addPipeLengthOption() {
            this.pipeLengthOptions.push({
                label: '',
                additional_price: '',
                _key: nextKey('pipe'),
            });
        },

        removePipeLengthOption(index) {
            this.pipeLengthOptions.splice(index, 1);
        },

        slugifyName() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            if (!nameInput || !slugInput || slugInput.dataset.manual === 'true') {
                return;
            }
            slugInput.value = slugify(nameInput.value);
        },

        markSlugManual() {
            const slugInput = document.getElementById('slug');
            if (slugInput) {
                slugInput.dataset.manual = 'true';
            }
        },

        baseSku() {
            const input = document.getElementById('base_sku');
            return (input?.value || 'SKU').toUpperCase().trim();
        },

        addVariationAttribute(name = '') {
            const isColor = name.toLowerCase() === 'color';

            this.variationAttributes.push({
                id: null,
                name,
                slug: isColor ? 'color' : slugify(name),
                values: [],
                newValue: '',
                newHex: '#000000',
                _key: nextKey('attr'),
            });
        },

        addPresetAttribute(name) {
            const exists = this.variationAttributes.some(
                (attribute) => attribute.name.toLowerCase() === name.toLowerCase(),
            );

            if (exists) {
                return;
            }

            if (name.toLowerCase() === 'color') {
                this.variationAttributes.push({
                    id: null,
                    name: 'Color',
                    slug: 'color',
                    values: Object.entries(DEFAULT_COLOR_PALETTE).map(([value, hex]) => ({ value, hex })),
                    newValue: '',
                    newHex: '#000000',
                    _key: nextKey('attr'),
                });

                return;
            }

            this.addVariationAttribute(name);
        },

        removeVariationAttribute(index) {
            this.variationAttributes.splice(index, 1);
        },

        addAttributeValue(attrIndex) {
            const attribute = this.variationAttributes[attrIndex];
            if (!attribute || isColorAttribute(attribute)) {
                return;
            }

            const value = (attribute.newValue || '').trim();
            if (!value) {
                return;
            }

            if (!attribute.values.some((entry) => valueLabel(entry) === value)) {
                attribute.values.push({ value, hex: '' });
            }

            attribute.newValue = '';
        },

        addColorAttributeValue(attrIndex) {
            const attribute = this.variationAttributes[attrIndex];
            if (!attribute || !isColorAttribute(attribute)) {
                return;
            }

            const value = (attribute.newValue || '').trim();
            const hex = normalizeHexInput(attribute.newHex);

            if (!value || !hex) {
                return;
            }

            const entry = { value, hex };
            const exists = attribute.values.some((item) => colorValueKey(item) === colorValueKey(entry));

            if (!exists) {
                attribute.values.push(entry);
            }

            attribute.newValue = '';
            attribute.newHex = hex;
        },

        removeAttributeValue(attrIndex, valueIndex) {
            this.variationAttributes[attrIndex]?.values.splice(valueIndex, 1);
        },

        expectedCombinationCount() {
            const counts = this.variationAttributes
                .filter((attribute) => attribute.name && attribute.values.length > 0)
                .map((attribute) => attribute.values.length);

            if (!counts.length) {
                return 0;
            }

            return counts.reduce((total, count) => total * count, 1);
        },

        canGenerateVariations() {
            return this.expectedCombinationCount() > 0;
        },

        combinationKey(attributeValues) {
            return (attributeValues || [])
                .map((row) => `${row.attribute_slug || slugify(row.attribute_name)}:${row.value}`)
                .sort()
                .join('|');
        },

        buildAttributeValuesFromCombo(combo) {
            return combo.map((item) => ({
                attribute_id: item.attribute_id || '',
                attribute_name: item.attribute_name,
                attribute_slug: item.attribute_slug,
                attribute_value_id: item.attribute_value_id || '',
                value: item.value,
                color_hex: item.color_hex || '',
                custom_value: '',
            }));
        },

        generateVariations() {
            const attributes = this.variationAttributes.filter(
                (attribute) => attribute.name.trim() && attribute.values.length > 0,
            );

            if (!attributes.length) {
                return;
            }

            const valueGroups = attributes.map((attribute) =>
                attribute.values.map((entry) => {
                    const normalized = normalizeAttributeValue(entry);

                    return {
                        attribute_id: attribute.id || '',
                        attribute_name: attribute.name.trim(),
                        attribute_slug: attribute.slug || slugify(attribute.name),
                        attribute_value_id: '',
                        value: normalized.value,
                        color_hex: normalized.hex || '',
                    };
                }),
            );

            const combinations = cartesian(valueGroups);
            const existingByKey = {};

            this.variants.forEach((variant) => {
                existingByKey[this.combinationKey(variant.attribute_values)] = variant;
            });

            const baseSku = this.baseSku();

            this.variants = combinations.map((combo, index) => {
                const attributeValues = this.buildAttributeValuesFromCombo(combo);
                const key = this.combinationKey(attributeValues);
                const existing = existingByKey[key];
                const label = combo.map((item) => item.value).join(' / ');
                const skuSuffix = combo.map((item) => slugify(item.value)).filter(Boolean).join('-');

                if (existing) {
                    return {
                        ...existing,
                        attribute_values: attributeValues,
                        variant_name: existing.variant_name || label,
                        _key: existing._key || nextKey('variant'),
                    };
                }

                return {
                    id: null,
                    sku: skuSuffix ? `${baseSku}-${skuSuffix}`.toUpperCase() : `${baseSku}-${index + 1}`,
                    variant_name: label,
                    price: this.defaultPrice || '',
                    sale_price: this.defaultSalePrice || '',
                    wholesale_price: this.defaultWholesalePrice || '',
                    dealer_price: this.defaultDealerPrice || '',
                    cost_price: '',
                    stock_quantity: 0,
                    low_stock_threshold: 5,
                    is_default: index === 0,
                    is_active: true,
                    attribute_values: attributeValues,
                    image_id: null,
                    image_url: null,
                    image_preview: null,
                    remove_image: false,
                    _key: nextKey('variant'),
                };
            });

            if (this.variants.length && !this.variants.some((variant) => variant.is_default)) {
                this.variants[0].is_default = true;
            }
        },

        rebuildVariationAttributesFromVariants() {
            if (this.variationAttributes.length) {
                return;
            }

            const map = {};

            this.variants.forEach((variant) => {
                (variant.attribute_values || []).forEach((row) => {
                    const name = row.attribute_name || '';
                    if (!name) {
                        return;
                    }

                    const slug = row.attribute_slug || slugify(name);
                    if (!map[slug]) {
                        map[slug] = {
                            id: row.attribute_id || null,
                            name,
                            slug,
                            values: [],
                            newValue: '',
                            newHex: '#000000',
                            _key: nextKey('attr'),
                        };
                    }

                    const value = row.value || row.custom_value;
                    if (!value) {
                        return;
                    }

                    if (slug === 'color') {
                        const entry = {
                            value,
                            hex: normalizeHexInput(row.color_hex) || '#CCCCCC',
                        };

                        if (!map[slug].values.some((item) => colorValueKey(item) === colorValueKey(entry))) {
                            map[slug].values.push(entry);
                        }

                        return;
                    }

                    if (!map[slug].values.some((item) => valueLabel(item) === value)) {
                        map[slug].values.push({ value, hex: '' });
                    }
                });
            });

            this.variationAttributes = Object.values(map);
        },

        addVariant() {
            const attributeValues = this.variationAttributes
                .filter((attribute) => attribute.name)
                .map((attribute) => {
                    const first = attribute.values[0] ? normalizeAttributeValue(attribute.values[0]) : { value: '', hex: '' };

                    return {
                        attribute_id: attribute.id || '',
                        attribute_name: attribute.name,
                        attribute_slug: attribute.slug || slugify(attribute.name),
                        attribute_value_id: '',
                        value: first.value,
                        color_hex: first.hex,
                        custom_value: '',
                    };
                });

            this.variants.push({
                id: null,
                sku: '',
                variant_name: '',
                price: this.defaultPrice || '',
                sale_price: this.defaultSalePrice || '',
                wholesale_price: this.defaultWholesalePrice || '',
                dealer_price: this.defaultDealerPrice || '',
                cost_price: '',
                stock_quantity: 0,
                low_stock_threshold: 5,
                is_default: this.variants.length === 0,
                is_active: true,
                attribute_values: attributeValues,
                image_id: null,
                image_url: null,
                image_preview: null,
                remove_image: false,
                _key: nextKey('variant'),
            });
        },

        removeVariant(index) {
            this.variants.splice(index, 1);
            if (this.variants.length && !this.variants.some((variant) => variant.is_default)) {
                this.variants[0].is_default = true;
            }
        },

        setDefaultVariant(index) {
            this.variants.forEach((variant, i) => {
                variant.is_default = i === index;
            });
        },

        variantLabel(variant) {
            if (variant.variant_name) {
                return variant.variant_name;
            }

            const labels = (variant.attribute_values || [])
                .map((row) => row.value || row.custom_value)
                .filter(Boolean);

            return labels.length ? labels.join(' / ') : 'Variation';
        },

        variantImagePreview(variant) {
            if (variant.remove_image) {
                return null;
            }

            return variant.image_preview || variant.image_url || null;
        },

        previewVariantImage(event, index) {
            const file = event.target.files?.[0];
            const variant = this.variants[index];
            if (!variant || !file) {
                return;
            }

            variant.remove_image = false;
            const reader = new FileReader();
            reader.onload = (e) => {
                variant.image_preview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        clearVariantImage(index) {
            const variant = this.variants[index];
            if (!variant) {
                return;
            }

            variant.image_preview = null;
            variant.image_url = null;
            variant.remove_image = true;
        },

        addProductAttribute() {
            this.productAttributes.push({
                attribute_id: '',
                attribute_value_id: '',
                custom_value: '',
            });
        },

        removeProductAttribute(index) {
            this.productAttributes.splice(index, 1);
        },

        attributeOptions(attributeId) {
            const attr = (config.allAttributes || []).find((a) => String(a.id) === String(attributeId));
            return attr?.values || [];
        },

        previewImages(event) {
            this.imagePreviews = [];
            Array.from(event.target.files).forEach((file) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreviews.push({ name: file.name, url: e.target.result });
                };
                reader.readAsDataURL(file);
            });
        },

        markImageRemoved(id) {
            if (!this.removedImages.includes(id)) {
                this.removedImages.push(id);
            }
        },

        isImageRemoved(id) {
            return this.removedImages.includes(id);
        },
    }));
});
