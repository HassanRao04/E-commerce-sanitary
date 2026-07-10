<?php

namespace App\Http\Requests\Admin;

use App\Support\HomepageSections;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomepageSectionsRequest extends FormRequest
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
        $rules = [
            'sections' => ['required', 'array'],
        ];

        foreach (HomepageSections::orderedKeys() as $key) {
            $rules["sections.{$key}"] = ['required', 'array'];
            $rules["sections.{$key}.enabled"] = ['nullable', 'boolean'];

            if (in_array($key, HomepageSections::carouselKeys(), true)) {
                $rules["sections.{$key}.title"] = ['required', 'string', 'max:120'];
                $rules["sections.{$key}.subtitle"] = ['nullable', 'string', 'max:500'];
                $rules["sections.{$key}.badge"] = ['nullable', 'string', 'max:60'];
                $rules["sections.{$key}.badge_class"] = ['nullable', 'string', 'max:120'];
                $rules["sections.{$key}.theme"] = ['required', Rule::in(['default', 'muted', 'sale'])];
                $rules["sections.{$key}.view_all_label"] = ['nullable', 'string', 'max:60'];
                $rules["sections.{$key}.collection"] = ['nullable', 'string', 'max:60'];
                $rules["sections.{$key}.mode"] = ['required', Rule::in(['auto', 'manual'])];
                $rules["sections.{$key}.limit"] = ['required', 'integer', 'min:1', 'max:24'];
                $rules["sections.{$key}.product_ids"] = ['nullable', 'array'];
                $rules["sections.{$key}.product_ids.*"] = ['integer', 'exists:products,id'];
            }

            if (in_array($key, [HomepageSections::CATEGORIES, HomepageSections::BRANDS], true)) {
                $rules["sections.{$key}.title"] = ['required', 'string', 'max:120'];
                $rules["sections.{$key}.eyebrow"] = ['nullable', 'string', 'max:60'];
                $rules["sections.{$key}.limit"] = ['required', 'integer', 'min:1', 'max:24'];
            }

            if ($key === HomepageSections::TESTIMONIALS) {
                $rules["sections.{$key}.badge"] = ['nullable', 'string', 'max:60'];
                $rules["sections.{$key}.title"] = ['required', 'string', 'max:120'];
                $rules["sections.{$key}.subtitle"] = ['nullable', 'string', 'max:500'];
                $rules["sections.{$key}.limit"] = ['required', 'integer', 'min:1', 'max:12'];
            }

            if ($key === HomepageSections::NEWSLETTER) {
                $rules["sections.{$key}.title"] = ['required', 'string', 'max:120'];
                $rules["sections.{$key}.subtitle"] = ['nullable', 'string', 'max:500'];
                $rules["sections.{$key}.offer"] = ['nullable', 'string', 'max:120'];
                $rules["sections.{$key}.offer_code"] = ['nullable', 'string', 'max:40'];
                $rules["sections.{$key}.theme"] = ['required', Rule::in(['dark', 'light'])];
            }

            if ($key === HomepageSections::CTA) {
                $rules["sections.{$key}.title"] = ['required', 'string', 'max:120'];
                $rules["sections.{$key}.subtitle"] = ['nullable', 'string', 'max:500'];
                $rules["sections.{$key}.button_text"] = ['required', 'string', 'max:60'];
                $rules["sections.{$key}.button_url"] = ['nullable', 'string', 'max:500'];
            }
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $sections = $this->input('sections', []);

        foreach (HomepageSections::carouselKeys() as $key) {
            if (! isset($sections[$key]['product_ids']) && isset($sections[$key]['product_ids_text'])) {
                $sections[$key]['product_ids'] = HomepageSections::normalizeProductIds($sections[$key]['product_ids_text']);
            }
        }

        $this->merge(['sections' => $sections]);
    }
}
