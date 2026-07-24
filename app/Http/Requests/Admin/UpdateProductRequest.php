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
            ProductRequestRules::offerTiers(),
            ProductRequestRules::pipeLengthOptions(),
            ProductRequestRules::images($productId),
        );
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'product_type' => $this->input('product_type', 'simple'),
            'is_featured' => $this->boolean('is_featured'),
            'is_new_arrival' => $this->boolean('is_new_arrival'),
            'is_best_seller' => $this->boolean('is_best_seller'),
            'is_project_suitable' => $this->boolean('is_project_suitable'),
            'offers_enabled' => $this->boolean('offers_enabled'),
            'pipe_length_enabled' => $this->boolean('pipe_length_enabled'),
        ];

        if ($this->has('offer_tiers')) {
            $payload['offer_tiers'] = collect($this->input('offer_tiers', []))->map(function ($row) {
                $row['free_shipping'] = filter_var($row['free_shipping'] ?? false, FILTER_VALIDATE_BOOLEAN);

                return $row;
            })->all();
        }

        $this->merge($payload);
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
