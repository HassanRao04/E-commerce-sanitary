<?php

namespace App\Http\Requests\Storefront;

use App\Enums\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && ! auth()->user()->isStaff();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('billing_same_as_shipping')) {
            $this->merge(['billing_same_as_shipping' => true]);
        } else {
            $this->merge([
                'billing_same_as_shipping' => $this->boolean('billing_same_as_shipping'),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $enabledMethods = array_map(
            fn (PaymentMethod $method): string => $method->value,
            app(PaymentService::class)->enabledMethods(),
        );

        $rules = [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'payment_method' => ['required', Rule::in($enabledMethods)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'billing_same_as_shipping' => ['nullable', 'boolean'],
        ];

        return array_merge($rules, $this->customerAddressRules());
    }

    /** @return array<string, mixed> */
    private function customerAddressRules(): array
    {
        $rules = [];

        if ($this->filled('shipping_address_id')) {
            $rules['shipping_address_id'] = [
                'required',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()->id),
            ];
        } else {
            $rules = array_merge($rules, $this->addressRules('shipping_'));
        }

        if ($this->boolean('billing_same_as_shipping')) {
            return $rules;
        }

        if ($this->filled('billing_address_id')) {
            $rules['billing_address_id'] = [
                'nullable',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()->id),
            ];
        } else {
            $rules = array_merge($rules, $this->addressRules('billing_'));
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    private function addressRules(string $prefix): array
    {
        return [
            $prefix.'full_name' => ['nullable', 'string', 'max:255'],
            $prefix.'phone' => ['nullable', 'string', 'max:30'],
            $prefix.'line1' => ['required', 'string', 'max:255'],
            $prefix.'line2' => ['nullable', 'string', 'max:255'],
            $prefix.'city' => ['required', 'string', 'max:100'],
            $prefix.'state' => ['nullable', 'string', 'max:100'],
            $prefix.'postal_code' => ['nullable', 'string', 'max:20'],
            $prefix.'country' => ['nullable', 'string', 'max:100'],
        ];
    }
}
