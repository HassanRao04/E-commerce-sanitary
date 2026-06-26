<?php

namespace App\Services;

use App\DataTransferObjects\PaymentWebhookDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentWebhookService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentService $payments,
    ) {}

    public function process(PaymentMethod $method, array $payload, ?string $eventType = null): PaymentVerificationDTO
    {
        $log = PaymentWebhookLog::create([
            'gateway' => $method->value,
            'event_type' => $eventType,
            'payload' => $payload,
            'processed' => false,
        ]);

        $gateway = $this->gateways->resolve($method);
        $result = $gateway->handleWebhook(new PaymentWebhookDTO(
            gateway: $method,
            eventType: $eventType,
            payload: $payload,
        ));

        $order = $this->resolveOrder($result, $payload);

        if ($order) {
            $this->payments->applyVerification($order, $result);
        }

        $log->update(['processed' => true]);

        return $result;
    }

    public function methodFromRoute(string $gateway): PaymentMethod
    {
        $normalized = Str::lower(Str::replace('-', '_', $gateway));

        return match ($normalized) {
            'cod', 'cash_on_delivery' => PaymentMethod::Cod,
            'bank_transfer', 'bank-transfer' => PaymentMethod::BankTransfer,
            'stripe' => PaymentMethod::Stripe,
            'jazzcash', 'jazz_cash' => PaymentMethod::Jazzcash,
            'easypaisa', 'easy_paisa' => PaymentMethod::Easypaisa,
            'payfast', 'pay_fast' => PaymentMethod::Payfast,
            default => throw new InvalidArgumentException("Unsupported payment webhook gateway [{$gateway}]."),
        };
    }

    /** @param array<string, mixed> $payload */
    private function resolveOrder(PaymentVerificationDTO $result, array $payload): ?Order
    {
        $reference = $result->gatewayReference
            ?? $payload['order_number']
            ?? $payload['pp_BillReference']
            ?? $payload['basket_id']
            ?? null;

        if (! $reference) {
            return null;
        }

        return Order::query()
            ->where('order_number', $reference)
            ->orWhere('tracking_token', $reference)
            ->first();
    }
}
