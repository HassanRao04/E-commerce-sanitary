<?php

namespace App\Services\Couriers;

use App\Contracts\CourierInterface;
use App\Contracts\CourierProviderInterface;
use App\Models\CourierProvider;
use InvalidArgumentException;

class CourierProviderManager
{
    /** @var array<string, CourierInterface> */
    private array $providers = [];

    public function __construct()
    {
        foreach (config('couriers.providers', []) as $slug => $providerClass) {
            $provider = app($providerClass);

            if (! $provider instanceof CourierInterface) {
                throw new InvalidArgumentException("Courier provider [{$slug}] must implement ".CourierInterface::class.'.');
            }

            $this->providers[$provider->slug()] = $provider;
        }
    }

    public function resolve(string $slug, bool $requireEnabled = false): CourierInterface
    {
        $provider = $this->providers[$slug] ?? null;

        if (! $provider) {
            throw new InvalidArgumentException("Courier provider [{$slug}] is not registered.");
        }

        if ($requireEnabled && ! $provider->isEnabled()) {
            throw new InvalidArgumentException("Courier provider [{$slug}] is not enabled.");
        }

        return $provider;
    }

    /** @deprecated Use resolve() — kept for backward compatibility */
    public function gateway(string $slug, bool $requireEnabled = false): CourierProviderInterface
    {
        return $this->resolve($slug, $requireEnabled);
    }

    public function resolveForShipment(?CourierProvider $courierProvider, bool $requireEnabled = false): CourierInterface
    {
        if ($courierProvider) {
            return $this->resolve($courierProvider->slug, $requireEnabled);
        }

        return $this->resolve('manual');
    }

    public function has(string $slug): bool
    {
        return isset($this->providers[$slug]);
    }

    /** @return array<string, CourierInterface> */
    public function all(): array
    {
        return $this->providers;
    }

    /** @return array<CourierInterface> */
    public function registered(): array
    {
        return array_values($this->providers);
    }

    /** @return array<CourierInterface> */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->providers,
            fn (CourierInterface $provider): bool => $provider->isEnabled(),
        ));
    }
}
