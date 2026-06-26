<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function __construct()
    {
        foreach ([
            CashOnDeliveryGateway::class,
            BankTransferGateway::class,
            StripeGateway::class,
            JazzCashGateway::class,
            EasypaisaGateway::class,
            PayFastGateway::class,
        ] as $gatewayClass) {
            $gateway = app($gatewayClass);
            $this->gateways[$gateway->method()->value] = $gateway;
        }
    }

    public function gateway(PaymentMethod $method): PaymentGatewayInterface
    {
        return $this->resolve($method, requireEnabled: true);
    }

    public function resolve(PaymentMethod $method, bool $requireEnabled = false): PaymentGatewayInterface
    {
        $gateway = $this->gateways[$method->value] ?? null;

        if (! $gateway) {
            throw new InvalidArgumentException("Payment gateway [{$method->value}] is not registered.");
        }

        if ($requireEnabled && ! $gateway->isEnabled()) {
            throw new InvalidArgumentException("Payment gateway [{$method->value}] is not enabled.");
        }

        return $gateway;
    }

    /** @return array<PaymentGatewayInterface> */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->gateways,
            fn (PaymentGatewayInterface $gateway): bool => $gateway->isEnabled(),
        ));
    }
}
