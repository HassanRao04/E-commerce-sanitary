<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentWebhookException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentWebhookService
{
    /** Keys stripped from the persisted payload so card/credential data is never stored. */
    private const REDACTED_KEYS = [
        'card', 'cvv', 'cvc', 'pan', 'card_number', 'cardnumber',
        'password', 'secret', 'token', 'access_token', 'api_key', 'authorization',
    ];

    /** Tolerance when comparing provider amounts against the order total. */
    private const AMOUNT_EPSILON = 0.01;

    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentService $payments,
        private readonly OrderWorkflowService $workflow,
    ) {}

    /**
     * Authenticate and apply an inbound webhook.
     *
     * Every check is fail-closed: the order is only touched once the signature,
     * gateway, reference, amount, currency and order state have all been proven.
     *
     * @param  array<string, mixed>  $payload  Parsed body, used for business data only.
     * @param  string  $rawBody  Byte-exact body, used for signature verification only.
     * @param  array<string, array<int, string|null>>  $headers
     *
     * @throws PaymentWebhookException
     */
    public function process(
        PaymentMethod $method,
        array $payload,
        ?string $eventType = null,
        string $rawBody = '',
        array $headers = [],
    ): PaymentVerificationDTO {
        $log = PaymentWebhookLog::create([
            'gateway' => $method->value,
            'event_type' => $eventType,
            'payload' => $this->redact($payload),
            'processed' => false,
        ]);

        $gateway = $this->resolveEnabledGateway($method);

        $webhook = new PaymentWebhookDTO(
            gateway: $method,
            eventType: $eventType,
            payload: $payload,
            rawBody: $rawBody,
            headers: $headers,
        );

        if (! $gateway->verifySignature($webhook)) {
            $this->reject($method, $eventType, PaymentWebhookException::signatureInvalid());
        }

        $result = $gateway->handleWebhook($webhook);

        $order = $this->resolveOrder($method, $eventType, $result);

        $this->assertGatewayMatchesOrder($method, $eventType, $order);
        $this->assertOrderIsPayable($method, $eventType, $order);

        if ($result->isSuccessful()) {
            if ($duplicate = $this->detectDuplicate($order, $result, $log)) {
                $log->update(['processed' => true]);

                Log::info('Payment webhook ignored as duplicate.', [
                    'gateway' => $method->value,
                    'event_type' => $eventType,
                    'order_number' => $order->order_number,
                    'reason' => $duplicate,
                ]);

                return $result;
            }

            $this->assertAmountMatches($method, $eventType, $order, $result);
            $this->assertCurrencyMatches($method, $eventType, $order, $result);
            $this->assertTransactionIdPresent($method, $eventType, $result);
        }

        $this->payments->applyVerification($order, $result, $method);

        $log->update(['processed' => true]);

        Log::info('Payment webhook processed.', [
            'gateway' => $method->value,
            'event_type' => $eventType,
            'order_number' => $order->order_number,
            'status' => $result->status->value,
            'transaction_id' => $result->transactionId,
            'event_id' => $result->eventId,
        ]);

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

    /**
     * A gateway that is switched off must present no webhook surface at all.
     */
    private function resolveEnabledGateway(PaymentMethod $method): PaymentGatewayInterface
    {
        try {
            return $this->gateways->gateway($method);
        } catch (InvalidArgumentException) {
            $this->reject($method, null, PaymentWebhookException::gatewayDisabled());
        }
    }

    private function resolveOrder(PaymentMethod $method, ?string $eventType, PaymentVerificationDTO $result): Order
    {
        $reference = $result->gatewayReference;

        if (! is_string($reference) || $reference === '') {
            $this->reject($method, $eventType, PaymentWebhookException::invalidPayload('missing_order_reference'));
        }

        // Order number only. Tracking tokens are shared with customers and must
        // never double as a payment authorisation reference.
        $order = Order::query()->where('order_number', $reference)->first();

        if (! $order) {
            $this->reject($method, $eventType, PaymentWebhookException::orderNotFound());
        }

        return $order;
    }

    private function assertGatewayMatchesOrder(PaymentMethod $method, ?string $eventType, Order $order): void
    {
        if ($order->payment_method !== $method) {
            $this->reject($method, $eventType, PaymentWebhookException::gatewayMismatch(), $order);
        }
    }

    private function assertOrderIsPayable(PaymentMethod $method, ?string $eventType, Order $order): void
    {
        if ($this->workflow->isCancelled((string) $order->status)) {
            $this->reject($method, $eventType, PaymentWebhookException::orderNotPayable('order_cancelled'), $order);
        }

        if ($this->workflow->isReturned((string) $order->status)) {
            $this->reject($method, $eventType, PaymentWebhookException::orderNotPayable('order_returned'), $order);
        }

        if (in_array($order->payment_status, [PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
            $this->reject($method, $eventType, PaymentWebhookException::orderNotPayable('order_refunded'), $order);
        }
    }

    /**
     * @return string|null Reason this delivery is a repeat, or null when it is new.
     */
    private function detectDuplicate(Order $order, PaymentVerificationDTO $result, PaymentWebhookLog $log): ?string
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return 'order_already_paid';
        }

        if (filled($result->transactionId)) {
            $alreadyRecorded = Payment::query()
                ->where('order_id', $order->id)
                ->where('transaction_id', $result->transactionId)
                ->successful()
                ->exists();

            if ($alreadyRecorded) {
                return 'transaction_already_recorded';
            }
        }

        if (filled($result->eventId)) {
            $alreadyProcessed = PaymentWebhookLog::query()
                ->where('gateway', $log->gateway)
                ->where('id', '!=', $log->id)
                ->where('processed', true)
                ->where('payload->id', $result->eventId)
                ->exists();

            if ($alreadyProcessed) {
                return 'event_already_processed';
            }
        }

        return null;
    }

    private function assertAmountMatches(PaymentMethod $method, ?string $eventType, Order $order, PaymentVerificationDTO $result): void
    {
        if ($result->amount === null) {
            $this->reject($method, $eventType, PaymentWebhookException::invalidPayload('missing_amount'), $order);
        }

        if (abs($result->amount - (float) $order->grand_total) > self::AMOUNT_EPSILON) {
            $this->reject($method, $eventType, PaymentWebhookException::amountMismatch(), $order);
        }
    }

    private function assertCurrencyMatches(PaymentMethod $method, ?string $eventType, Order $order, PaymentVerificationDTO $result): void
    {
        $expected = Str::upper((string) config('shop.currency', 'PKR'));

        if (! filled($result->currency)) {
            $this->reject($method, $eventType, PaymentWebhookException::invalidPayload('missing_currency'), $order);
        }

        if (Str::upper($result->currency) !== $expected) {
            $this->reject($method, $eventType, PaymentWebhookException::currencyMismatch(), $order);
        }
    }

    private function assertTransactionIdPresent(PaymentMethod $method, ?string $eventType, PaymentVerificationDTO $result): void
    {
        if (! filled($result->transactionId)) {
            $this->reject($method, $eventType, PaymentWebhookException::invalidPayload('missing_transaction_id'));
        }
    }

    /** @throws PaymentWebhookException */
    private function reject(PaymentMethod $method, ?string $eventType, PaymentWebhookException $exception, ?Order $order = null): never
    {
        Log::warning('Payment webhook rejected.', [
            'gateway' => $method->value,
            'event_type' => $eventType,
            'reason' => $exception->reason,
            'status' => $exception->statusCode,
            'order_number' => $order?->order_number,
        ]);

        throw $exception;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redact(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->redact($value);

                continue;
            }

            if (is_string($key) && in_array(Str::lower($key), self::REDACTED_KEYS, true)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }
}
