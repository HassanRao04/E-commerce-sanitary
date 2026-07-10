<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorefrontContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('homepage.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'contact.page_title' => ['nullable', 'string', 'max:120'],
            'contact.intro' => ['nullable', 'string', 'max:1000'],
            'contact.business_hours' => ['nullable', 'string', 'max:255'],
            'contact.show_order_tracking' => ['nullable', 'boolean'],
            'contact.order_tracking_label' => ['nullable', 'string', 'max:120'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.contact_phone' => ['nullable', 'string', 'max:50'],
            'contact.whatsapp' => ['nullable', 'string', 'max:50'],
            'contact.address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
