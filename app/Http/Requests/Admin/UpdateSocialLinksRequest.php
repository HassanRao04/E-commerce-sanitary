<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('homepage.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'social.show_in_top_bar' => ['nullable', 'boolean'],
            'social.show_in_footer' => ['nullable', 'boolean'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:500'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
        ];
    }
}
