import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('productForm', (config = {}) => ({
        tab: 'general',
        productType: config.productType || 'simple',
        variants: config.variants || [],
        productAttributes: config.productAttributes || [],
        variantAttributes: config.variantAttributes || [],
        imagePreviews: [],
        removedImages: config.removedImages || [],

        init() {
            if (this.productType === 'variable' && this.variants.length === 0) {
                this.addVariant();
            }

            this.$watch('productType', (value) => {
                if (value === 'variable' && this.variants.length === 0) {
                    this.addVariant();
                }
            });
        },

        slugifyName() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            if (!nameInput || !slugInput || slugInput.dataset.manual === 'true') {
                return;
            }
            slugInput.value = nameInput.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        },

        markSlugManual() {
            const slugInput = document.getElementById('slug');
            if (slugInput) {
                slugInput.dataset.manual = 'true';
            }
        },

        addVariant() {
            this.variants.push({
                id: null,
                sku: '',
                variant_name: '',
                price: '',
                sale_price: '',
                cost_price: '',
                stock_quantity: 0,
                low_stock_threshold: 5,
                is_default: this.variants.length === 0,
                is_active: true,
                attribute_values: this.variantAttributes.map((attr) => ({
                    attribute_id: attr.id,
                    attribute_value_id: '',
                    custom_value: '',
                })),
            });
        },

        removeVariant(index) {
            this.variants.splice(index, 1);
            if (this.variants.length && !this.variants.some((v) => v.is_default)) {
                this.variants[0].is_default = true;
            }
        },

        setDefaultVariant(index) {
            this.variants.forEach((variant, i) => {
                variant.is_default = i === index;
            });
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
