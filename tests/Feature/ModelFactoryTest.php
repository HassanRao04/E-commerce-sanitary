<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_product_factory_creates_default_variant(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->default_variant_id);
        $this->assertTrue($product->variants()->exists());
    }

    public function test_customer_factory_assigns_customer_role(): void
    {
        $customer = Customer::factory()->create();

        $this->assertTrue($customer->user->hasRole('customer'));
    }

    public function test_order_factory_can_create_paid_order(): void
    {
        $order = Order::factory()->paid()->create();

        $this->assertEquals(\App\Enums\PaymentStatus::Paid, $order->payment_status);
        $this->assertNotEmpty($order->tracking_token);
    }
}
