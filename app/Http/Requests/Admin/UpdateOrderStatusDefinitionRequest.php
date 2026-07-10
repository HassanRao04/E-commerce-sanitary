<?php

namespace App\Http\Requests\Admin;

use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.workflow.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $colors = app(OrderWorkflowService::class)->badgeColorOptions();
        $status = $this->route('orderStatus');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('order_statuses', 'slug')->ignore($status?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'badge_color' => ['required', Rule::in($colors)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'customer_group' => ['nullable', Rule::in(['pending', 'processing', 'delivered', 'excluded'])],
            'show_in_progress' => ['sometimes', 'boolean'],
            'is_terminal' => ['sometimes', 'boolean'],
            'is_cancellation' => ['sometimes', 'boolean'],
            'is_return' => ['sometimes', 'boolean'],
            'is_delivered' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
