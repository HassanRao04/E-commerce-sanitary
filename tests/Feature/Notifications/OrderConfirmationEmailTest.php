<?php

namespace Tests\Feature\Notifications;

use App\Enums\PaymentMethod;
use App\Jobs\SendOrderConfirmationJob;
use App\Mail\OrderConfirmationMail;
use App\Models\Invoice;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_order_confirmation_email_is_sent_after_checkout(): void
    {
        Mail::fake();

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();
        $variant = $product->defaultVariant;

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
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

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($customer, $order, $product): bool {
            return $mail->hasTo($customer->email)
                && $mail->presentation['orderNumber'] === $order->order_number
                && $mail->presentation['customerName'] === $customer->name
                && collect($mail->presentation['items'])->contains(
                    fn (array $item): bool => $item['name'] === $product->name && $item['quantity'] === 1
                );
        });

        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
        ]);
    }

    public function test_order_confirmation_email_includes_invoice_pdf_attachment(): void
    {
        Mail::fake();

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

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order): bool {
            $attachments = $mail->attachments();

            return count($attachments) === 1
                && $attachments[0]->as === 'Invoice-'.$order->order_number.'.pdf'
                && filled($mail->invoice->pdf_path)
                && Storage::disk('local')->exists($mail->invoice->pdf_path);
        });

        $invoice = Invoice::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($invoice?->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
    }

    public function test_order_confirmation_job_is_queued(): void
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

        Queue::assertPushed(SendOrderConfirmationJob::class, function (SendOrderConfirmationJob $job) use ($order): bool {
            return $job->orderId === $order->id;
        });
    }

    public function test_order_confirmation_email_is_skipped_when_customer_opted_out(): void
    {
        Mail::fake();

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        NotificationPreference::query()->create([
            'user_id' => $customer->id,
            'email_orders' => false,
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

        Mail::assertNothingSent();
    }

    public function test_order_confirmation_service_generates_invoice_number(): void
    {
        Mail::fake();

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

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order): bool {
            $invoiceNumber = $mail->presentation['invoiceNumber'] ?? null;

            return filled($invoiceNumber)
                && Invoice::query()->where('order_id', $order->id)->where('invoice_number', $invoiceNumber)->exists();
        });
    }
}
