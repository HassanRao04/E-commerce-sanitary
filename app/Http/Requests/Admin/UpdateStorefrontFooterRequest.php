<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorefrontFooterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('homepage.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'footer.tagline' => ['nullable', 'string', 'max:500'],
            'footer.copyright_name' => ['nullable', 'string', 'max:120'],
            'footer.bottom_meta' => ['nullable', 'string', 'max:255'],
            'footer.newsletter.title' => ['nullable', 'string', 'max:120'],
            'footer.newsletter.copy' => ['nullable', 'string', 'max:255'],
            'footer.categories.mode' => ['nullable', 'in:auto,manual'],
            'footer.categories.limit' => ['nullable', 'integer', 'min:1', 'max:12'],
            'footer.categories.category_ids' => ['nullable', 'array'],
            'footer.categories.category_ids.*' => ['integer', 'exists:categories,id'],
            'footer.columns' => ['nullable', 'array'],
            'footer.columns.*.heading' => ['nullable', 'string', 'max:120'],
            'footer.columns.*.links' => ['nullable', 'array'],
            'footer.columns.*.links.*.label' => ['nullable', 'string', 'max:120'],
            'footer.columns.*.links.*.route' => ['nullable', 'string', 'max:120'],
            'footer.columns.*.links.*.url' => ['nullable', 'string', 'max:255'],
        ];
    }
}
