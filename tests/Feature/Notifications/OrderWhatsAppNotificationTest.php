<?php

namespace Tests\Feature\Notifications;

use App\Enums\PaymentMethod;
use App\Jobs\SendOrderWhatsAppJob;
use App\Models\ActivityLog;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

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

        SiteSetting::current()->update([
            'whatsapp_notifications_enabled' => true,
        ]);
    }

    public function test_order_whatsapp_is_sent_after_checkout(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'shipping_line1' => '123 Main Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ]);

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);

        Http::assertSentCount(1);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'order.whatsapp_sent',
            'model_type' => Order::class,
            'model_id' => $order->id,
        ]);
    }

    public function test_order_whatsapp_job_is_queued(): void
    {
        Queue::fake();

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'shipping_line1' => '123 Main Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ]);

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);

        Queue::assertPushed(SendOrderWhatsAppJob::class, function (SendOrderWhatsAppJob $job) use ($order): bool {
            return $job->orderId === $order->id;
        });
    }

    public function test_order_whatsapp_is_not_sent_when_customer_opted_out(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        NotificationPreference::query()->create([
            'user_id' => $customer->id,
            'sms_orders' => false,
        ]);

        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'shipping_line1' => '123 Main Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ]);

        Http::assertNothingSent();
    }

    public function test_order_whatsapp_skips_when_configuration_is_placeholder(): void
    {
        config([
            'services.whatsapp.token' => 'your_meta_access_token',
            'services.whatsapp.phone_number_id' => 'your_phone_number_id',
        ]);

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'shipping_line1' => '123 Main Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ]);

        Http::assertNothingSent();
        $this->assertSame(0, ActivityLog::query()->where('action', 'order.whatsapp_sent')->count());
    }

    public function test_order_whatsapp_is_not_duplicated(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'shipping_line1' => '123 Main Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ]);

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);

        Http::assertSentCount(1);

        dispatch(new SendOrderWhatsAppJob($order->id));

        Http::assertSentCount(1);
        $this->assertSame(1, ActivityLog::query()
            ->where('action', 'order.whatsapp_sent')
            ->where('model_id', $order->id)
            ->count());
    }
}
