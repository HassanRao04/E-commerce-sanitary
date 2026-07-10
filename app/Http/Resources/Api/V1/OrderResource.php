<?php

namespace App\Http\Resources\Api\V1;

use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $workflow = app(OrderWorkflowService::class);

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'tracking_token' => $this->tracking_token,
            'status' => $this->status,
            'status_label' => $workflow->label($this->status),
            'payment_status' => $this->payment_status?->value,
            'payment_method' => $this->payment_method?->value,
            'subtotal' => (float) $this->subtotal,
            'discount_total' => (float) $this->discount_total,
            'shipping_total' => (float) $this->shipping_total,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'currency' => config('shop.currency', 'PKR'),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
