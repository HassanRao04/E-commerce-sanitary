<?php

namespace App\Services;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
    ) {}

    public function initiate(Order $order, PaymentMethod $method): PaymentVerificationDTO
    {
        return DB::transaction(function () use ($order, $method) {
            $gateway = $this->gateways->gateway($method);
            $intent = new PaymentIntentDTO(
                order: $order,
                method: $method,
                amount: (float) $order->grand_total,
                currency: config('shop.currency', 'PKR'),
            );

            $result = $gateway->charge($intent);

            Payment::create([
                'order_id' => $order->id,
                'gateway' => $method,
                'transaction_id' => $result->transactionId,
                'gateway_reference' => $result->gatewayReference ?? $order->order_number,
                'amount' => $order->grand_total,
                'currency' => config('shop.currency', 'PKR'),
                'status' => $result->status,
                'paid_at' => $result->isSuccessful() ? now() : null,
                'metadata' => $result->metadata,
            ]);

            if ($result->isSuccessful()) {
                $this->markOrderPaid($order);
            }

            return $result;
        });
    }

    public function applyVerification(Order $order, PaymentVerificationDTO $result): Payment
    {
        return DB::transaction(function () use ($order, $result) {
            $payment = Payment::query()
                ->where('order_id', $order->id)
                ->latest('id')
                ->first();

            if (! $payment) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'gateway' => $order->payment_method,
                    'gateway_reference' => $result->gatewayReference ?? $order->order_number,
                    'amount' => $order->grand_total,
                    'currency' => config('shop.currency', 'PKR'),
                    'status' => $result->status,
                ]);
            }

            $payment->update([
                'transaction_id' => $result->transactionId ?? $payment->transaction_id,
                'gateway_reference' => $result->gatewayReference ?? $payment->gateway_reference,
                'status' => $result->status,
                'paid_at' => $result->isSuccessful() ? now() : $payment->paid_at,
                'metadata' => array_merge($payment->metadata ?? [], $result->metadata),
            ]);

            if ($result->isSuccessful()) {
                $this->markOrderPaid($order);
            } elseif ($result->status === PaymentStatus::Failed) {
                $order->update(['payment_status' => PaymentStatus::Failed]);
            }

            return $payment->fresh();
        });
    }

    /** @return array<PaymentMethod> */
    public function enabledMethods(): array
    {
        return array_map(
            fn ($gateway) => $gateway->method(),
            $this->gateways->enabled(),
        );
    }

    private function markOrderPaid(Order $order): void
    {
        $order->update([
            'payment_status' => PaymentStatus::Paid,
            'status' => $order->status === OrderStatus::Pending
                ? OrderStatus::Confirmed
                : $order->status,
        ]);
    }
}
