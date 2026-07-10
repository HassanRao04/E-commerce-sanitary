<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckoutRulesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('checkout_rules.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'minimum_order_enabled' => ['nullable', 'boolean'],
            'minimum_order_amount' => ['required', 'numeric', 'min:0'],
            'coupons_enabled' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        return [
            'minimum_order_enabled' => (bool) ($validated['minimum_order_enabled'] ?? false),
            'minimum_order_amount' => (float) $validated['minimum_order_amount'],
            'coupons_enabled' => (bool) ($validated['coupons_enabled'] ?? false),
        ];
    }
}
