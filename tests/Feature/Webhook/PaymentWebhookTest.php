<?php

namespace Tests\Feature\Webhook;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Database\Seeders\OrderStatusSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        // Roles are required because Order::factory() creates a User, which is
        // assigned the `customer` role on creation.
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(OrderStatusSeeder::class);

        config([
            'payments.enabled.stripe' => true,
            'payments.stripe.webhook_secret' => self::WEBHOOK_SECRET,
            'shop.currency' => 'PKR',
        ]);
    }

    // ---------------------------------------------------------------------
    // A. Valid signed webhook
    // ---------------------------------------------------------------------

    public function test_valid_signed_webhook_marks_order_paid(): void
    {
        $order = $this->stripeOrder();

        $this->postSignedStripe($this->stripePayload($order))
            ->assertOk()
            ->assertJsonPath('status', PaymentStatus::Paid->value);

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame('confirmed', $order->status);

        $payment = Payment::query()->where('order_id', $order->id)->sole();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentMethod::Stripe, $payment->gateway);
        $this->assertSame('pi_test_1', $payment->transaction_id);
    }

    // ---------------------------------------------------------------------
    // B/C/D. Signature rejection
    // ---------------------------------------------------------------------

    public function test_missing_signature_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $this->postStripe($this->stripePayload($order))->assertStatus(400);

        $this->assertOrderUntouched($order);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $this->postStripe($this->stripePayload($order), 't=1,v1=deadbeef')->assertStatus(400);

        $this->assertOrderUntouched($order);
    }

    public function test_signature_from_wrong_secret_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $this->postSignedStripe($this->stripePayload($order), secret: 'whsec_wrong_secret')
            ->assertStatus(400);

        $this->assertOrderUntouched($order);
    }

    public function test_signature_outside_timestamp_tolerance_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $this->postSignedStripe($this->stripePayload($order), timestamp: time() - 3600)
            ->assertStatus(400);

        $this->assertOrderUntouched($order);
    }

    // ---------------------------------------------------------------------
    // E. Disabled gateways
    // ---------------------------------------------------------------------

    public function test_disabled_gateway_is_rejected_even_with_valid_signature(): void
    {
        config(['payments.enabled.stripe' => false]);

        $order = $this->stripeOrder();

        $this->postSignedStripe($this->stripePayload($order))->assertStatus(404);

        $this->assertOrderUntouched($order);
    }

    public function test_disabled_jazzcash_gateway_cannot_be_reached(): void
    {
        config(['payments.enabled.jazzcash' => false]);

        $order = $this->stripeOrder(['payment_method' => PaymentMethod::Jazzcash]);

        $this->postJson(route('webhooks.payments', 'jazzcash'), [
            'pp_ResponseCode' => '000',
            'pp_BillReference' => $order->order_number,
            'pp_TxnRefNo' => 'JC123456',
        ])->assertStatus(404);

        $this->assertOrderUntouched($order);
    }

    public function test_enabled_gateway_without_signature_support_fails_closed(): void
    {
        config(['payments.enabled.jazzcash' => true]);

        $order = $this->stripeOrder(['payment_method' => PaymentMethod::Jazzcash]);

        $this->postJson(route('webhooks.payments', 'jazzcash'), [
            'pp_ResponseCode' => '000',
            'pp_BillReference' => $order->order_number,
            'pp_TxnRefNo' => 'JC123456',
        ])->assertStatus(400);

        $this->assertOrderUntouched($order);
    }

    public function test_unknown_gateway_returns_404(): void
    {
        $this->postJson(route('webhooks.payments', 'unknown-gateway'), [])->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // F. Cross-gateway hijack
    // ---------------------------------------------------------------------

    public function test_stripe_webhook_cannot_mark_a_cod_order_paid(): void
    {
        $order = $this->stripeOrder(['payment_method' => PaymentMethod::Cod]);

        $this->postSignedStripe($this->stripePayload($order))->assertStatus(422);

        $order->refresh();

        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(PaymentMethod::Cod, $order->payment_method);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_stripe_webhook_cannot_mark_a_bank_transfer_order_paid(): void
    {
        $order = $this->stripeOrder(['payment_method' => PaymentMethod::BankTransfer]);

        $this->postSignedStripe($this->stripePayload($order))->assertStatus(422);

        $order->refresh();

        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(PaymentMethod::BankTransfer, $order->payment_method);
    }

    public function test_cod_webhook_endpoint_cannot_settle_a_cod_order(): void
    {
        $order = $this->stripeOrder(['payment_method' => PaymentMethod::Cod]);

        $this->postJson(route('webhooks.payments', 'cod'), [
            'order_number' => $order->order_number,
            'status' => 'paid',
        ])->assertStatus(400);

        $this->assertOrderUntouched($order);
    }

    public function test_bank_transfer_webhook_endpoint_cannot_settle_an_order(): void
    {
        $order = $this->stripeOrder(['payment_method' => PaymentMethod::BankTransfer]);

        $this->postJson(route('webhooks.payments', 'bank-transfer'), [
            'order_number' => $order->order_number,
            'status' => 'paid',
        ])->assertStatus(400);

        $this->assertOrderUntouched($order);
    }

    // ---------------------------------------------------------------------
    // G/M. Reference and payload validation
    // ---------------------------------------------------------------------

    public function test_unknown_order_reference_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $payload = $this->stripePayload($order);
        $payload['data']['object']['metadata']['order_number'] = 'ORD-0000-NOPE';

        $this->postSignedStripe($payload)->assertStatus(404);

        $this->assertOrderUntouched($order);
    }

    public function test_tracking_token_is_not_accepted_as_a_payment_reference(): void
    {
        $order = $this->stripeOrder();

        $payload = $this->stripePayload($order);
        $payload['data']['object']['metadata']['order_number'] = $order->tracking_token;

        $this->postSignedStripe($payload)->assertStatus(404);

        $this->assertOrderUntouched($order);
    }

    public function test_payload_without_order_reference_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $payload = $this->stripePayload($order);
        unset($payload['data']['object']['metadata']);

        $this->postSignedStripe($payload)->assertStatus(422);

        $this->assertOrderUntouched($order);
    }

    public function test_payload_without_transaction_id_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $payload = $this->stripePayload($order);
        unset($payload['data']['object']['id']);

        $this->postSignedStripe($payload)->assertStatus(422);

        $this->assertOrderUntouched($order);
    }

    // ---------------------------------------------------------------------
    // H/I. Amount and currency
    // ---------------------------------------------------------------------

    public function test_wrong_amount_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $this->postSignedStripe($this->stripePayload($order, amount: 1.00))->assertStatus(422);

        $this->assertOrderUntouched($order);
    }

    public function test_missing_amount_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $payload = $this->stripePayload($order);
        unset($payload['data']['object']['amount_received']);

        $this->postSignedStripe($payload)->assertStatus(422);

        $this->assertOrderUntouched($order);
    }

    public function test_wrong_currency_is_rejected(): void
    {
        $order = $this->stripeOrder();

        $this->postSignedStripe($this->stripePayload($order, currency: 'usd'))->assertStatus(422);

        $this->assertOrderUntouched($order);
    }

    // ---------------------------------------------------------------------
    // J/L. Replay and idempotency
    // ---------------------------------------------------------------------

    public function test_duplicate_webhook_delivery_is_idempotent(): void
    {
        $order = $this->stripeOrder();
        $payload = $this->stripePayload($order);

        $this->postSignedStripe($payload)->assertOk();
        $firstPaidAt = Payment::query()->where('order_id', $order->id)->sole()->paid_at;

        $this->postSignedStripe($payload)->assertOk();

        $this->assertDatabaseCount('payments', 1);
        $payment = Payment::query()->where('order_id', $order->id)->sole();
        $this->assertEquals($firstPaidAt, $payment->paid_at);
        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
    }

    public function test_already_paid_order_is_left_unchanged(): void
    {
        $order = $this->stripeOrder([
            'payment_status' => PaymentStatus::Paid,
            'status' => 'confirmed',
        ]);

        $this->postSignedStripe($this->stripePayload($order))->assertOk();

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
    }

    // ---------------------------------------------------------------------
    // K. Order state protection
    // ---------------------------------------------------------------------

    public function test_cancelled_order_cannot_become_paid(): void
    {
        $order = $this->stripeOrder(['status' => 'cancelled']);

        $this->postSignedStripe($this->stripePayload($order))->assertStatus(422);

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame('cancelled', $order->status);
    }

    public function test_returned_order_cannot_become_paid(): void
    {
        $order = $this->stripeOrder(['status' => 'returned']);

        $this->postSignedStripe($this->stripePayload($order))->assertStatus(422);

        $this->assertSame(PaymentStatus::Pending, $order->refresh()->payment_status);
    }

    public function test_refunded_order_cannot_become_paid(): void
    {
        $order = $this->stripeOrder(['payment_status' => PaymentStatus::Refunded]);

        $this->postSignedStripe($this->stripePayload($order))->assertStatus(422);

        $this->assertSame(PaymentStatus::Refunded, $order->refresh()->payment_status);
    }

    public function test_soft_deleted_order_cannot_become_paid(): void
    {
        $order = $this->stripeOrder();
        $orderNumber = $order->order_number;
        $payload = $this->stripePayload($order);
        $order->delete();

        $this->postSignedStripe($payload)->assertStatus(404);

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(
            PaymentStatus::Pending->value,
            Order::withTrashed()->where('order_number', $orderNumber)->sole()->payment_status->value,
        );
    }

    // ---------------------------------------------------------------------
    // Responses must not leak internals
    // ---------------------------------------------------------------------

    public function test_rejection_response_does_not_leak_internal_details(): void
    {
        $order = $this->stripeOrder();

        $response = $this->postStripe($this->stripePayload($order));

        $response->assertStatus(400);
        $body = $response->getContent();

        $this->assertStringNotContainsString(self::WEBHOOK_SECRET, $body);
        $this->assertStringNotContainsString('Exception', $body);
        $this->assertStringNotContainsString('vendor', $body);
    }

    public function test_webhook_secret_is_never_persisted_in_the_log_payload(): void
    {
        $order = $this->stripeOrder();

        $payload = $this->stripePayload($order);
        $payload['data']['object']['card'] = '4242424242424242';

        $this->postSignedStripe($payload)->assertOk();

        $this->assertDatabaseHas('payment_webhook_logs', ['gateway' => 'stripe', 'processed' => true]);
        $this->assertDatabaseMissing('payment_webhook_logs', ['payload' => '4242424242424242']);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /** @param array<string, mixed> $attributes */
    private function stripeOrder(array $attributes = []): Order
    {
        return Order::factory()
            ->state(new Sequence([
                'payment_method' => PaymentMethod::Stripe,
                'payment_status' => PaymentStatus::Pending,
                'status' => 'pending',
                'subtotal' => 5000.00,
                'discount_total' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'grand_total' => 5000.00,
            ]))
            ->create($attributes);
    }

    /** @return array<string, mixed> */
    private function stripePayload(
        Order $order,
        ?float $amount = null,
        string $currency = 'pkr',
        string $eventId = 'evt_test_1',
        string $paymentIntent = 'pi_test_1',
    ): array {
        return [
            'id' => $eventId,
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $paymentIntent,
                    'status' => 'succeeded',
                    'amount_received' => (int) round(($amount ?? (float) $order->grand_total) * 100),
                    'currency' => $currency,
                    'metadata' => ['order_number' => $order->order_number],
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function postSignedStripe(array $payload, ?string $secret = null, ?int $timestamp = null): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? self::WEBHOOK_SECRET);

        return $this->postStripe($payload, "t={$timestamp},v1={$signature}");
    }

    /** @param array<string, mixed> $payload */
    private function postStripe(array $payload, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        if ($signature !== null) {
            $server['HTTP_STRIPE_SIGNATURE'] = $signature;
        }

        return $this->call('POST', route('webhooks.payments', 'stripe'), [], [], [], $server, $body);
    }

    private function assertOrderUntouched(Order $order): void
    {
        $expected = $order->payment_status;

        $this->assertSame($expected, $order->refresh()->payment_status);
        $this->assertDatabaseCount('payments', 0);
    }
}
