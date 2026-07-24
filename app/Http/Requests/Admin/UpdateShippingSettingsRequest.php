<?php

namespace App\Http\Requests\Admin;

use App\Enums\ShippingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShippingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shipping.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'flat_rate_enabled' => ['sometimes', 'boolean'],
            'flat_rate_amount' => ['nullable', 'numeric', 'min:0'],
            'product_rate_enabled' => ['sometimes', 'boolean'],
            'category_rate_enabled' => ['sometimes', 'boolean'],
            'free_shipping_enabled' => ['sometimes', 'boolean'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'default_method' => ['required', Rule::enum(ShippingMethod::class)],
            'product_rates' => ['nullable', 'array'],
            'product_rates.*.product_id' => ['required_with:product_rates', 'integer', 'exists:products,id'],
            'product_rates.*.amount' => ['required_with:product_rates', 'numeric', 'min:0'],
            'product_rates.*.free_shipping' => ['sometimes', 'boolean'],
            'product_rates.*.is_active' => ['sometimes', 'boolean'],
            'category_rates' => ['nullable', 'array'],
            'category_rates.*.category_id' => ['required_with:category_rates', 'integer', 'exists:categories,id'],
            'category_rates.*.amount' => ['required_with:category_rates', 'numeric', 'min:0'],
            'category_rates.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'flat_rate_enabled' => $this->boolean('flat_rate_enabled'),
            'product_rate_enabled' => $this->boolean('product_rate_enabled'),
            'category_rate_enabled' => $this->boolean('category_rate_enabled'),
            'free_shipping_enabled' => $this->boolean('free_shipping_enabled'),
        ];

        if ($this->has('product_rates')) {
            $payload['product_rates'] = collect($this->input('product_rates', []))->map(function ($row) {
                $row['free_shipping'] = filter_var($row['free_shipping'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $row['is_active'] = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

                return $row;
            })->all();
        }

        if ($this->has('category_rates')) {
            $payload['category_rates'] = collect($this->input('category_rates', []))->map(function ($row) {
                $row['is_active'] = filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

                return $row;
            })->all();
        }

        $this->merge($payload);
    }
}
