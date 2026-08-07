<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\Notifications\OrderWhatsAppNotificationService;
use App\Services\Notifications\WhatsAppNotificationService;
use App\Support\SocialLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_phone_digits_and_normalization_reuse_existing_whatsapp_helpers(): void
    {
        $this->assertSame('923314324807', SocialLinks::phoneDigits('+92-331-4324807'));
        $this->assertSame('923114118052', SocialLinks::normalizePhoneForWhatsapp('03114118052'));
        $this->assertSame('923314324807', SocialLinks::normalizePhoneForWhatsapp('03314324807'));
        $this->assertSame('923001234567', SocialLinks::normalizePhoneForWhatsapp('03001234567'));
        $this->assertSame('923001234567', SocialLinks::normalizePhoneForWhatsapp('3001234567'));
    }

    public function test_it_sends_whatsapp_template_via_meta_cloud_api(): void
    {
        config([
            'services.whatsapp.token' => 'EAAG-test-token-value',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.message_mode' => 'template',
            'services.whatsapp.order_template' => 'hello_world',
            'services.whatsapp.order_template_language' => 'en_US',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        $sent = app(WhatsAppNotificationService::class)->sendOrderConfirmation('03001234567', 99);

        $this->assertTrue($sent);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://graph.facebook.com/v21.0/123456789/messages'
                && $request['to'] === '923001234567'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'hello_world'
                && $request['template']['language']['code'] === 'en_US';
        });
    }

    public function test_it_sends_whatsapp_text_when_text_mode_enabled(): void
    {
        config([
            'services.whatsapp.token' => 'EAAG-test-token-value',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.message_mode' => 'text',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        $sent = app(WhatsAppNotificationService::class)->sendOrderConfirmation(
            '03001234567',
            99,
            [],
            'Hello order',
        );

        $this->assertTrue($sent);

        Http::assertSent(function ($request): bool {
            return $request['type'] === 'text'
                && $request['text']['body'] === 'Hello order';
        });
    }

    public function test_it_rejects_placeholder_configuration_values(): void
    {
        config([
            'services.whatsapp.token' => 'your_meta_access_token',
            'services.whatsapp.phone_number_id' => 'your_phone_number_id',
        ]);

        $service = app(WhatsAppNotificationService::class);

        $this->assertFalse($service->isConfigured());
        $this->assertSame([
            'WHATSAPP_TOKEN is still a placeholder value',
            'WHATSAPP_PHONE_NUMBER_ID is still a placeholder value',
        ], $service->configurationIssues());
    }

    public function test_it_handles_invalid_oauth_response_without_throwing(): void
    {
        config([
            'services.whatsapp.token' => 'EAAG-valid-looking-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.message_mode' => 'template',
            'services.whatsapp.order_template' => 'hello_world',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token - Cannot parse access token',
                    'code' => 190,
                ],
            ], 401),
        ]);

        Log::spy();

        $sent = app(WhatsAppNotificationService::class)->sendOrderConfirmation('03001234567', 42);

        $this->assertFalse($sent);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_it_retries_retryable_api_failures(): void
    {
        config([
            'services.whatsapp.token' => 'EAAG-valid-looking-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.message_mode' => 'template',
            'services.whatsapp.order_template' => 'hello_world',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Service unavailable'],
            ], 503),
        ]);

        $this->expectException(\App\Exceptions\WhatsAppRetryableException::class);

        app(WhatsAppNotificationService::class)->sendOrderConfirmation('03001234567', 42);
    }

    public function test_order_whatsapp_message_contains_required_fields(): void
    {
        $order = Order::query()->with('invoice')->first();
        $this->assertNotNull($order);

        $message = app(OrderWhatsAppNotificationService::class)->buildMessage(
            $order,
            'INV-TEST-0001',
            SiteSetting::current(),
        );

        $settings = SiteSetting::current();

        $this->assertStringContainsString($settings->displayName(), $message);
        $this->assertStringContainsString('Order Number: '.$order->order_number, $message);
        $this->assertStringContainsString('Invoice Number: INV-TEST-0001', $message);
        $this->assertStringContainsString('Order Total:', $message);
        $this->assertStringContainsString('Payment Method:', $message);
        $this->assertStringContainsString('Order Status:', $message);
        $this->assertStringContainsString('Track Order:', $message);
        $this->assertStringContainsString('Support:', $message);
    }

    public function test_hello_world_template_has_no_body_parameters(): void
    {
        $order = Order::query()->first();
        $this->assertNotNull($order);

        $invoice = new Invoice([
            'invoice_number' => 'INV-TEST-0001',
        ]);

        $parameters = app(OrderWhatsAppNotificationService::class)->buildTemplateBodyParameters(
            $order,
            $invoice,
        );

        $this->assertSame([], $parameters);
    }
}
