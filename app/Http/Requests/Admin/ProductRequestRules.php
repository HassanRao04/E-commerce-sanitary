<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;

class ProductRequestRules
{
    public static function base(?int $productId = null): array
    {
        return [
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'product_type' => ['required', Rule::in(['simple', 'variable'])],
            'base_sku' => ['required', 'string', 'max:100', Rule::unique('products', 'base_sku')->ignore($productId)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'installation_type' => ['nullable', 'string', 'max:100'],
            'material' => ['nullable', 'string', 'max:100'],
            'warranty_text' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['boolean'],
            'is_new_arrival' => ['boolean'],
            'is_best_seller' => ['boolean'],
            'is_project_suitable' => ['boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function simplePricing(): array
    {
        return [
            'price' => ['required_if:product_type,simple', 'nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required_if:product_type,simple', 'nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public static function variants(?int $productId = null): array
    {
        return [
            'variants' => ['required_if:product_type,variable', 'nullable', 'array', 'min:1'],
            'variants.*.id' => [
                'nullable',
                'integer',
                $productId
                    ? Rule::exists('product_variants', 'id')->where('product_id', $productId)
                    : Rule::exists('product_variants', 'id'),
            ],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.variant_name' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_default' => ['boolean'],
            'variants.*.is_active' => ['boolean'],
            'variants.*.attribute_values' => ['nullable', 'array'],
            'variants.*.attribute_values.*.attribute_id' => ['required', 'exists:attributes,id'],
            'variants.*.attribute_values.*.attribute_value_id' => ['nullable', 'exists:attribute_values,id'],
            'variants.*.attribute_values.*.custom_value' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function productAttributes(): array
    {
        return [
            'product_attributes' => ['nullable', 'array'],
            'product_attributes.*.attribute_id' => ['required', 'exists:attributes,id'],
            'product_attributes.*.attribute_value_id' => ['nullable', 'exists:attribute_values,id'],
            'product_attributes.*.custom_value' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function images(?int $productId = null): array
    {
        $imageIdRule = $productId
            ? Rule::exists('product_images', 'id')->where('product_id', $productId)
            : Rule::in([]);

        return [
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer', $imageIdRule],
            'primary_image_id' => ['nullable', 'integer', $productId ? $imageIdRule : 'prohibited'],
        ];
    }
}
