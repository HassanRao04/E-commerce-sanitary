<?php

namespace App\Http\Requests\Admin;

use App\Models\CourierProvider;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shipping.manage') ?? false;
    }

    public function rules(): array
    {
        /** @var Order|null $order */
        $order = $this->route('order');

        return [
            'courier_provider_id' => [
                'required',
                'integer',
                Rule::exists('courier_providers', 'id')->where(function ($query) {
                    $query->where('slug', '!=', 'manual')->where('is_active', true);
                }),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var Order|null $order */
            $order = $this->route('order');

            if ($order && $order->shipments()->exists()) {
                $validator->errors()->add('courier_provider_id', 'This order already has a shipment.');
            }
        });
    }

    public function courierProvider(): CourierProvider
    {
        return CourierProvider::query()->findOrFail($this->integer('courier_provider_id'));
    }
}
