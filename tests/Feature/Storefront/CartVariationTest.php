<?php

namespace Tests\Feature\Storefront;

use App\Enums\PaymentMethod;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartVariationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_variable_product_requires_variant_when_adding_to_cart(): void
    {
        $product = $this->createVariableProductWithOptions();

        $this->from(route('shop.products.show', $product))
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('shop.products.show', $product))
            ->assertSessionHasErrors('product_variant_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_add_to_cart_stores_variant_id_and_options(): void
    {
        $product = $this->createVariableProductWithOptions();
        $variant = $product->variants()->where('sku', 'VAR-BLK-24')->first();
        $this->assertNotNull($variant);

        $this->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('shop.cart.index'));

        $cartItem = CartItem::query()->first();
        $this->assertNotNull($cartItem);
        $this->assertSame($variant->id, $cartItem->product_variant_id);
        $this->assertSame([
            ['name' => 'Color', 'slug' => 'color', 'value' => 'Black'],
            ['name' => 'Size', 'slug' => 'size', 'value' => 'Small'],
        ], $cartItem->variant_options);

        $this->get(route('shop.cart.index'))
            ->assertOk()
            ->assertSee('Color:')
            ->assertSee('Black')
            ->assertSee('Size:')
            ->assertSee('Small');
    }

    public function test_checkout_snapshots_variant_options_and_decrements_variant_inventory(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $product = $this->createVariableProductWithOptions();
        $variant = $product->variants()->where('sku', 'VAR-BLK-24')->first();
        $warehouse = Warehouse::query()->where('is_default', true)->first();
        $this->assertNotNull($warehouse);

        $inventory = Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        $this->assertNotNull($inventory);
        $initialInventory = $inventory->quantity_on_hand;
        $initialVariantStock = $variant->fresh()->stock_quantity;

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
                'shipping_line1' => '123 Test Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ])
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);

        $orderItem = $order->items()->first();
        $this->assertSame($variant->id, $orderItem->product_variant_id);
        $this->assertSame([
            ['name' => 'Color', 'slug' => 'color', 'value' => 'Black'],
            ['name' => 'Size', 'slug' => 'size', 'value' => 'Small'],
        ], $orderItem->variant_options);
        $this->assertSame('Color: Black · Size: Small', $orderItem->variant_name);

        $this->assertSame($initialInventory - 1, $inventory->fresh()->quantity_on_hand);
        $this->assertSame($initialVariantStock - 1, $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'sale',
            'quantity' => -1,
        ]);
    }

    private function createVariableProductWithOptions(): Product
    {
        $product = Product::factory()->create([
            'product_type' => 'variable',
            'status' => \App\Enums\ProductStatus::Active,
        ]);

        $color = Attribute::query()->where('slug', 'color')->firstOrFail();
        $size = Attribute::query()->where('slug', 'size')->firstOrFail();
        $black = AttributeValue::query()
            ->where('attribute_id', $color->id)
            ->where('slug', 'black')
            ->firstOrFail();
        $small = AttributeValue::query()
            ->where('attribute_id', $size->id)
            ->where('slug', 'small')
            ->firstOrFail();

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-BLK-24',
            'variant_name' => 'Black / Small',
            'price' => 1500,
            'stock_quantity' => 8,
            'color_name' => 'Black',
            'color_hex' => '#000000',
            'size' => 'Small',
            'is_default' => true,
            'is_active' => true,
        ]);

        $variant->attributeValues()->createMany([
            ['attribute_id' => $color->id, 'attribute_value_id' => $black->id],
            ['attribute_id' => $size->id, 'attribute_value_id' => $small->id],
        ]);

        $warehouse = Warehouse::query()->where('is_default', true)->first();
        if ($warehouse) {
            Inventory::updateOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id],
                [
                    'quantity_on_hand' => 8,
                    'quantity_reserved' => 0,
                    'low_stock_threshold' => 5,
                ],
            );
        }

        return $product->fresh(['variants']);
    }
}
