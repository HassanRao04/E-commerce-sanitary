<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorefrontHeaderRequest extends FormRequest
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
        $platformKeys = array_keys(\App\Support\SocialLinks::platforms());

        return [
            'header.announcement.enabled' => ['nullable', 'boolean'],
            'header.announcement.text' => ['nullable', 'string', 'max:500'],
            'header.announcement.link_url' => ['nullable', 'string', 'max:500'],
            'header.announcement.link_label' => ['nullable', 'string', 'max:120'],
            'header.social.show_in_top_bar' => ['nullable', 'boolean'],
            'header.social.show_in_footer' => ['nullable', 'boolean'],
            'header.social_links' => ['nullable', 'array'],
            'header.social_links.*' => ['nullable', 'string', 'max:500'],
            'header.whatsapp' => ['nullable', 'string', 'max:50'],
            'header.nav_items' => ['required', 'array', 'min:1'],
            'header.nav_items.*.label' => ['required', 'string', 'max:60'],
            'header.nav_items.*.route' => ['nullable', 'string', 'max:120'],
            'header.nav_items.*.url' => ['nullable', 'string', 'max:500'],
            'header.nav_items.*.active_patterns' => ['nullable'],
            'header.nav_items.*.enabled' => ['nullable', 'boolean'],
            'header.nav_items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'header.nav_items.*.mega_menu' => ['nullable', 'boolean'],
            'header.nav_items.*.open_in_new_tab' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'header.nav_items.required' => 'Add at least one navigation link.',
            'header.nav_items.*.label.required' => 'Each menu link needs a label.',
        ];
    }
}
