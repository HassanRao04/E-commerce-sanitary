<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Services\Admin\InvoiceService;
use App\Services\Notifications\OrderConfirmationService;
use App\Support\OrderTrackingUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_track_order_url_uses_configured_app_url(): void
    {
        URL::forceRootUrl('https://shop.example.test');
        URL::forceScheme('https');

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $order->update(['tracking_token' => 'track-token-abc123']);

        $url = OrderTrackingUrl::forOrder($order->fresh());

        $this->assertSame(
            'https://shop.example.test/track-order?tracking_token=track-token-abc123',
            $url,
        );
    }

    public function test_track_order_url_without_token_uses_track_order_route(): void
    {
        URL::forceRootUrl('https://shop.example.test');
        URL::forceScheme('https');

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $order->update(['tracking_token' => null]);

        $this->assertSame(
            'https://shop.example.test/track-order',
            OrderTrackingUrl::forOrder($order->fresh()),
        );
    }

    public function test_get_track_order_with_valid_tracking_token_shows_order(): void
    {
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $order->update(['tracking_token' => 'valid-token-123']);

        $this->get(route('shop.orders.track', ['tracking_token' => 'valid-token-123']))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Order timeline', false);
    }

    public function test_get_track_order_with_invalid_tracking_token_shows_error(): void
    {
        $this->get(route('shop.orders.track', ['tracking_token' => 'missing-token']))
            ->assertRedirect(route('shop.orders.track', ['tracking_token' => 'missing-token']))
            ->assertSessionHas(
                'error',
                'We could not find an order matching that tracking reference. Please check the link and try again.',
            );
    }

    public function test_post_track_order_with_valid_order_number_and_email(): void
    {
        $order = Order::query()->first();
        $this->assertNotNull($order);

        $this->post(route('shop.orders.track.show'), [
            'order_number' => $order->order_number,
            'email' => $order->customer_email,
        ])->assertOk()->assertSee($order->order_number);
    }

    public function test_post_track_order_with_valid_tracking_token(): void
    {
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $order->update(['tracking_token' => 'post-token-456']);

        $this->post(route('shop.orders.track.show'), [
            'tracking_token' => 'post-token-456',
        ])->assertOk()->assertSee($order->order_number);
    }

    public function test_post_track_order_with_invalid_details_shows_error(): void
    {
        $this->from(route('shop.orders.track'))
            ->post(route('shop.orders.track.show'), [
                'order_number' => 'ORD-DOES-NOT-EXIST',
                'email' => 'missing@example.com',
            ])
            ->assertRedirect(route('shop.orders.track'))
            ->assertSessionHas(
                'error',
                'We could not find an order matching those details. Please check and try again.',
            );
    }

    public function test_order_confirmation_email_track_url_uses_configured_app_url(): void
    {
        URL::forceRootUrl('https://store.example.test');
        URL::forceScheme('https');

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $order->update(['tracking_token' => 'email-track-token']);

        $invoice = app(InvoiceService::class)->generateFromOrder($order->fresh());

        $presentation = app(OrderConfirmationService::class)->buildPresentation($order->fresh(), $invoice);

        $this->assertSame(
            'https://store.example.test/track-order?tracking_token=email-track-token',
            $presentation['trackOrderUrl'],
        );
    }
}
