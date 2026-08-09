<?php

namespace App\Services;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderWorkflowService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly OrderWorkflowService $workflow,
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

    /**
     * Apply a verified gateway result to an order.
     *
     * $gateway scopes the payment lookup so a webhook from one provider can
     * never settle a payment taken through another (e.g. Stripe marking a COD
     * payment paid). Callers must authenticate the webhook before calling this.
     */
    public function applyVerification(Order $order, PaymentVerificationDTO $result, PaymentMethod $gateway): Payment
    {
        return DB::transaction(function () use ($order, $result, $gateway) {
            $payment = Payment::query()
                ->where('order_id', $order->id)
                ->forGateway($gateway)
                ->latest('id')
                ->first();

            if (! $payment) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'gateway' => $gateway,
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
            'status' => $this->workflow->confirmedSlugForPayment((string) $order->status),
        ]);
    }
}
