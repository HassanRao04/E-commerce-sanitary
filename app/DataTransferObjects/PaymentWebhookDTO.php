<?php

namespace App\DataTransferObjects;

use App\Enums\PaymentMethod;

readonly class PaymentWebhookDTO
{
    /**
     * @param  array<string, mixed>  $payload  Parsed payload, for business processing only.
     * @param  string  $rawBody  Byte-exact request body. Signature checks must use this, never $payload.
     * @param  array<string, array<int, string|null>>  $headers
     */
    public function __construct(
        public PaymentMethod $gateway,
        public ?string $eventType,
        public array $payload,
        public string $rawBody = '',
        public array $headers = [],
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }
}
