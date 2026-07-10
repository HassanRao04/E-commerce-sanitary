<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::exists('order_statuses', 'slug')->where('is_active', true)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
