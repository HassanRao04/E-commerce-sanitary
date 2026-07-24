<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['product_offer_id', 'pipe_length_option_id'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'product_offer_id' => ['sometimes', 'nullable', 'integer', 'exists:product_offers,id'],
            'pipe_length_option_id' => ['sometimes', 'nullable', 'integer', 'exists:product_pipe_length_options,id'],
        ];
    }
}
