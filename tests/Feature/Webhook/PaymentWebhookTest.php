<?php

namespace Tests\Feature\Webhook;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_jazzcash_webhook_marks_order_paid(): void
    {
        $order = Order::query()->where('payment_status', PaymentStatus::Pending)->first();

        if (! $order) {
            $order = Order::factory()->create([
                'payment_method' => PaymentMethod::Jazzcash,
            ]);
        } else {
            $order->update(['payment_method' => PaymentMethod::Jazzcash]);
        }

        $this->postJson(route('webhooks.payments', 'jazzcash'), [
            'pp_ResponseCode' => '000',
            'pp_BillReference' => $order->order_number,
            'pp_TxnRefNo' => 'JC123456',
            'pp_ResponseMessage' => 'Success',
        ])->assertOk()
            ->assertJsonPath('status', PaymentStatus::Paid->value);

        $this->assertEquals(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => PaymentMethod::Jazzcash->value,
            'processed' => true,
        ]);
    }

    public function test_invalid_gateway_returns_404(): void
    {
        $this->postJson(route('webhooks.payments', 'unknown-gateway'), [])
            ->assertNotFound();
    }

    public function test_stripe_webhook_updates_order_when_reference_matches(): void
    {
        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::Stripe,
            'status' => 'pending',
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->postJson(route('webhooks.payments', 'stripe'), [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'status' => 'succeeded',
                    'metadata' => ['order_number' => $order->order_number],
                ],
            ],
        ])->assertOk();

        $this->assertEquals(PaymentStatus::Paid, $order->fresh()->payment_status);
    }
}
