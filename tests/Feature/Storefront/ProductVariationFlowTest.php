<?php

namespace Tests\Feature\Storefront;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_variable_product_page_renders_variation_controls(): void
    {
        $product = $this->createVariableProduct();

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Color')
            ->assertSee('Black')
            ->assertSee('Size')
            ->assertSee('Small');
    }

    public function test_admin_order_detail_shows_variant_options(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $product = $this->createVariableProduct();
        $variant = $product->variants()->where('sku', 'FLOW-BLK-S')->first();
        $this->assertNotNull($variant);

        $this->post(route('shop.cart.store'), [
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
                'payment_method' => 'cod',
                'shipping_line1' => '123 Test Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ])
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Color:')
            ->assertSee('Black')
            ->assertSee('Size:')
            ->assertSee('Small');
    }

    private function createVariableProduct(): Product
    {
        $product = Product::factory()->create([
            'product_type' => 'variable',
            'status' => 'active',
        ]);

        $color = Attribute::query()->where('slug', 'color')->firstOrFail();
        $size = Attribute::query()->where('slug', 'size')->firstOrFail();
        $black = AttributeValue::query()->where('attribute_id', $color->id)->where('slug', 'black')->firstOrFail();
        $small = AttributeValue::query()->where('attribute_id', $size->id)->where('slug', 'small')->firstOrFail();

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'FLOW-BLK-S',
            'variant_name' => 'Black / Small',
            'price' => 2500,
            'stock_quantity' => 10,
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
                    'quantity_on_hand' => 10,
                    'quantity_reserved' => 0,
                    'low_stock_threshold' => 5,
                ],
            );
        }

        $white = AttributeValue::query()->where('attribute_id', $color->id)->where('slug', 'white')->firstOrFail();
        $large = AttributeValue::query()->where('attribute_id', $size->id)->where('slug', 'large')->firstOrFail();

        $secondVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'FLOW-WHT-L',
            'variant_name' => 'White / Large',
            'price' => 2600,
            'stock_quantity' => 6,
            'color_name' => 'White',
            'color_hex' => '#FFFFFF',
            'size' => 'Large',
            'is_default' => false,
            'is_active' => true,
        ]);

        $secondVariant->attributeValues()->createMany([
            ['attribute_id' => $color->id, 'attribute_value_id' => $white->id],
            ['attribute_id' => $size->id, 'attribute_value_id' => $large->id],
        ]);

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereKeyNot($variant->id)
            ->forceDelete();

        $product->update(['default_variant_id' => $variant->id]);

        return $product->fresh(['variants']);
    }
}
