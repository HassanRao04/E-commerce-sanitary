<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChargeCalculationType;
use App\Enums\TaxType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTaxChargeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tax.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vat_enabled' => ['sometimes', 'boolean'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gst_enabled' => ['sometimes', 'boolean'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sales_tax_enabled' => ['sometimes', 'boolean'],
            'sales_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_tax_type' => ['required', Rule::enum(TaxType::class)],
            'service_charge_enabled' => ['sometimes', 'boolean'],
            'service_charge_type' => ['nullable', Rule::enum(ChargeCalculationType::class)],
            'service_charge_value' => ['nullable', 'numeric', 'min:0'],
            'handling_charge_enabled' => ['sometimes', 'boolean'],
            'handling_charge_type' => ['nullable', Rule::enum(ChargeCalculationType::class)],
            'handling_charge_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'vat_enabled' => $this->boolean('vat_enabled'),
            'gst_enabled' => $this->boolean('gst_enabled'),
            'sales_tax_enabled' => $this->boolean('sales_tax_enabled'),
            'service_charge_enabled' => $this->boolean('service_charge_enabled'),
            'handling_charge_enabled' => $this->boolean('handling_charge_enabled'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $defaultTax = TaxType::tryFrom((string) $this->input('default_tax_type'));

            if (! $defaultTax || $defaultTax === TaxType::None) {
                return;
            }

            $enabledField = match ($defaultTax) {
                TaxType::Vat => 'vat_enabled',
                TaxType::Gst => 'gst_enabled',
                TaxType::SalesTax => 'sales_tax_enabled',
                TaxType::None => null,
            };

            if ($enabledField && ! $this->boolean($enabledField)) {
                $validator->errors()->add(
                    'default_tax_type',
                    'The selected default tax type must be enabled.',
                );
            }
        });
    }
}
