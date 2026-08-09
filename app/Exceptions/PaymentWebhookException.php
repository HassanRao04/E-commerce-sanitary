<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejection of an inbound payment webhook.
 *
 * `$message` is returned to the caller, so it must never contain secrets,
 * provider internals or stack details. `$reason` is a stable machine-readable
 * code used for logging and metrics only.
 */
class PaymentWebhookException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function unknownGateway(): self
    {
        return new self('Unknown payment gateway.', Response::HTTP_NOT_FOUND, 'unknown_gateway');
    }

    public static function gatewayDisabled(): self
    {
        return new self('Unknown payment gateway.', Response::HTTP_NOT_FOUND, 'gateway_disabled');
    }

    public static function signatureMissing(): self
    {
        return new self('Webhook signature missing.', Response::HTTP_BAD_REQUEST, 'signature_missing');
    }

    public static function signatureInvalid(): self
    {
        return new self('Webhook signature verification failed.', Response::HTTP_BAD_REQUEST, 'signature_invalid');
    }

    public static function verificationUnavailable(): self
    {
        return new self('Webhook verification is not available for this gateway.', Response::HTTP_BAD_REQUEST, 'verification_unavailable');
    }

    public static function invalidPayload(string $reason = 'invalid_payload'): self
    {
        return new self('Webhook payload is invalid.', Response::HTTP_UNPROCESSABLE_ENTITY, $reason);
    }

    public static function orderNotFound(): self
    {
        return new self('Order reference not found.', Response::HTTP_NOT_FOUND, 'order_not_found');
    }

    public static function gatewayMismatch(): self
    {
        return new self('Gateway does not match the order payment method.', Response::HTTP_UNPROCESSABLE_ENTITY, 'gateway_mismatch');
    }

    public static function amountMismatch(): self
    {
        return new self('Payment amount does not match the order total.', Response::HTTP_UNPROCESSABLE_ENTITY, 'amount_mismatch');
    }

    public static function currencyMismatch(): self
    {
        return new self('Payment currency does not match the order currency.', Response::HTTP_UNPROCESSABLE_ENTITY, 'currency_mismatch');
    }

    public static function orderNotPayable(string $reason): self
    {
        return new self('Order is not in a payable state.', Response::HTTP_UNPROCESSABLE_ENTITY, $reason);
    }
}
