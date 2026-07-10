<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeroBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('homepage.manage') ?? false;
    }

    public function rules(): array
    {
        return $this->heroRules(requireImage: true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function heroRules(bool $requireImage = false): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:255'],
            'badge_class' => ['nullable', 'string', 'max:255'],
            'promo' => ['nullable', 'string', 'max:255'],
            'promo_detail' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'secondary_button_text' => ['nullable', 'string', 'max:255'],
            'secondary_button_url' => ['nullable', 'string', 'max:500'],
            'background' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:3'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => [$requireImage ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
