<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reviews.moderate') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reviews_enabled' => ['nullable', 'boolean'],
            'auto_approve' => ['nullable', 'boolean'],
            'show_on_homepage' => ['nullable', 'boolean'],
            'max_featured' => ['required', 'integer', 'min:1', 'max:12'],
            'homepage_mode' => ['required', Rule::in(['featured', 'latest'])],
        ];
    }
}
