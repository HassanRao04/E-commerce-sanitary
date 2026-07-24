<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('coupons.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($this->route('coupon')),
            ],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
            'influencer_id' => ['nullable', 'integer', 'exists:users,id'],
            'commission_enabled' => ['boolean'],
            'commission_type' => ['nullable', Rule::enum(CouponType::class), 'required_if:commission_enabled,1'],
            'commission_value' => ['nullable', 'numeric', 'min:0.01', 'required_if:commission_enabled,1'],
        ];
    }
}
