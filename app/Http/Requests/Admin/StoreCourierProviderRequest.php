<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shipping.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('courier_providers', 'slug')],
            'logo' => ['nullable', 'image', 'max:2048'],
            'api_base_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'pickup_address' => ['nullable', 'string', 'max:1000'],
            'pickup_city' => ['nullable', 'string', 'max:100'],
            'default_package_weight' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'tracking_url_template' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_sandbox' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
