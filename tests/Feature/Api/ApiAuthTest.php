<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_api_can_list_products_without_auth(): void
    {
        $this->getJson(route('api.v1.products.index'))
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_api_can_show_product_by_slug(): void
    {
        $product = Product::query()->active()->first()
            ?? Product::factory()->create();

        $this->getJson(route('api.v1.products.show', $product->slug))
            ->assertOk()
            ->assertJsonPath('data.slug', $product->slug);
    }

    public function test_customer_can_login_and_fetch_orders(): void
    {
        $user = User::where('email', 'ahmed@example.com')->first();
        $order = Order::query()->where('user_id', $user->id)->first();

        $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);

        Sanctum::actingAs($user);

        $this->getJson(route('api.v1.orders.index'))
            ->assertOk()
            ->assertJsonStructure(['data']);

        if ($order) {
            $this->getJson(route('api.v1.orders.show', $order))
                ->assertOk()
                ->assertJsonPath('data.order_number', $order->order_number);
        }
    }

    public function test_customer_cannot_view_other_users_order(): void
    {
        $owner = User::where('email', 'ahmed@example.com')->first();
        $other = User::factory()->customer()->create();
        $order = Order::query()->where('user_id', $owner->id)->first();

        if (! $order) {
            $this->markTestSkipped('No seeded order available.');
        }

        Sanctum::actingAs($other);

        $this->getJson(route('api.v1.orders.show', $order))->assertForbidden();
    }
}
