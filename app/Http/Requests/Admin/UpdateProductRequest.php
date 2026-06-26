<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.update') ?? false;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return array_merge(
            ProductRequestRules::base($productId),
            ProductRequestRules::simplePricing(),
            ProductRequestRules::variants($productId),
            ProductRequestRules::productAttributes(),
            ProductRequestRules::images($productId),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_type' => $this->input('product_type', 'simple'),
            'is_featured' => $this->boolean('is_featured'),
            'is_new_arrival' => $this->boolean('is_new_arrival'),
            'is_best_seller' => $this->boolean('is_best_seller'),
            'is_project_suitable' => $this->boolean('is_project_suitable'),
        ]);
    }

    public function messages(): array
    {
        return [
            'variants.required_if' => 'Add at least one variant for variable products.',
            'variants.min' => 'Add at least one variant for variable products.',
            'images.*.max' => 'Each image must be smaller than 5 MB.',
        ];
    }
}
