<?php

namespace Tests\Unit\Couriers;

use App\Contracts\CourierInterface;
use App\Enums\ShipmentBookingStatus;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use App\Services\Couriers\CourierProviderManager;
use App\Services\Couriers\CourierService;
use App\Services\Couriers\ManualCourierProvider;
use App\Services\Couriers\Providers\LeopardsCourierService;
use App\Services\Couriers\Providers\MnpCourierService;
use App\Services\Couriers\Providers\TcsCourierService;
use App\Services\Couriers\Providers\TraxCourierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierServiceArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_configured_providers_are_registered(): void
    {
        $manager = app(CourierProviderManager::class);

        foreach (array_keys(config('couriers.providers')) as $slug) {
            $this->assertTrue($manager->has($slug), "Provider [{$slug}] should be registered.");
            $this->assertInstanceOf(CourierInterface::class, $manager->resolve($slug));
        }
    }

    public function test_provider_services_implement_courier_interface(): void
    {
        $services = [
            ManualCourierProvider::class,
            TcsCourierService::class,
            LeopardsCourierService::class,
            MnpCourierService::class,
            TraxCourierService::class,
        ];

        foreach ($services as $serviceClass) {
            $service = app($serviceClass);
            $this->assertInstanceOf(CourierInterface::class, $service);
            $this->assertNotSame('', $service->slug());
            $this->assertNotSame('', $service->displayName());
        }
    }

    public function test_unconfigured_tcs_returns_credentials_error_without_http_calls(): void
    {
        $order = Order::firstOrFail();
        $request = new \App\DataTransferObjects\ShipmentBookingRequestDTO(order: $order);

        CourierProvider::where('slug', 'tcs')->update([
            'api_base_url' => null,
            'credentials' => null,
        ]);

        $tcs = app(TcsCourierService::class);
        $result = $tcs->bookShipment($request);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(ShipmentBookingStatus::Failed, $result->status);
        $this->assertStringContainsString('credentials', strtolower($result->message ?? ''));
    }

    public function test_stub_providers_return_not_implemented_booking(): void
    {
        $order = Order::firstOrFail();
        $request = new \App\DataTransferObjects\ShipmentBookingRequestDTO(order: $order);

        $leopards = app(LeopardsCourierService::class);
        $result = $leopards->bookShipment($request);

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContainsString('not implemented', strtolower($result->message ?? ''));
    }

    public function test_courier_service_resolves_provider_for_shipment(): void
    {
        $provider = CourierProvider::where('slug', 'leopards')->firstOrFail();
        $shipment = Shipping::firstOrFail();
        $shipment->update([
            'courier_provider_id' => $provider->id,
            'courier_name' => $provider->name,
        ]);

        $service = app(CourierService::class);
        $resolved = $service->providerForShipment($shipment->fresh('courierProvider'));

        $this->assertSame('leopards', $resolved->slug());
        $this->assertInstanceOf(LeopardsCourierService::class, $resolved);
    }

    public function test_courier_service_track_delegates_to_provider(): void
    {
        $provider = CourierProvider::where('slug', 'leopards')->firstOrFail();
        $shipment = Shipping::firstOrFail();
        $shipment->update([
            'courier_provider_id' => $provider->id,
            'courier_name' => $provider->name,
        ]);

        $result = app(CourierService::class)->track($shipment->fresh('courierProvider'));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not implemented', strtolower($result->message ?? ''));
    }

    public function test_enabled_providers_require_database_and_config_flags(): void
    {
        CourierProvider::where('slug', 'mnp')->update(['is_active' => true]);
        config(['couriers.enabled.mnp' => false]);

        $this->assertFalse(app(MnpCourierService::class)->isEnabled());

        config(['couriers.enabled.mnp' => true]);

        $this->assertTrue(app(MnpCourierService::class)->isEnabled());
    }

    public function test_manual_provider_is_always_enabled(): void
    {
        $this->assertTrue(app(CourierService::class)->provider('manual')->isEnabled());
    }
}
