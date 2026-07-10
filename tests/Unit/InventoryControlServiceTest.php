<?php

namespace Tests\Unit;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\InventoryControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryControlServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryControlService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->inventory = app(InventoryControlService::class);
    }

    public function test_snapshot_reflects_warehouse_on_hand_reserved_and_available(): void
    {
        [$variant, $item] = $this->createTrackedVariant(onHand: 12, reserved: 3, threshold: 4);

        $snapshot = $this->inventory->snapshot($variant);

        $this->assertSame(12, $snapshot['on_hand']);
        $this->assertSame(3, $snapshot['reserved']);
        $this->assertSame(9, $snapshot['available']);
        $this->assertSame(4, $snapshot['low_stock_threshold']);
        $this->assertSame('in_stock', $snapshot['status']);

        $item->update(['quantity_on_hand' => 4, 'quantity_reserved' => 1]);
        $variant->update(['stock_quantity' => 4]);

        $snapshot = $this->inventory->snapshot($variant->fresh());

        $this->assertSame(3, $snapshot['available']);
        $this->assertSame('low_stock', $snapshot['status']);
    }

    public function test_reserve_and_release_update_available_quantity(): void
    {
        [$variant] = $this->createTrackedVariant(onHand: 5, reserved: 0);

        $this->inventory->reserve($variant, 2, Product::class, 1, 'Test hold');
        $this->inventory->reserve($variant, 1, Product::class, 2, 'Second hold');

        $snapshot = $this->inventory->snapshot($variant->fresh());
        $this->assertSame(3, $snapshot['reserved']);
        $this->assertSame(2, $snapshot['available']);

        $this->inventory->release($variant, 1, Product::class, 1, 'Partial release');

        $snapshot = $this->inventory->snapshot($variant->fresh());
        $this->assertSame(2, $snapshot['reserved']);
        $this->assertSame(3, $snapshot['available']);
    }

    public function test_cart_reservation_prevents_overselling_last_unit(): void
    {
        [$variant] = $this->createTrackedVariant(onHand: 1, reserved: 0);
        $product = $variant->product;

        app(CartService::class)->addItem($product->id, $variant->id, 1);

        $snapshot = $this->inventory->snapshot($variant->fresh());
        $this->assertSame(1, $snapshot['reserved']);
        $this->assertSame(0, $snapshot['available']);

        $this->expectException(ValidationException::class);

        app(CartService::class)->addItem($product->id, $variant->id, 1);
    }

    public function test_removing_cart_item_releases_reserved_stock(): void
    {
        [$variant] = $this->createTrackedVariant(onHand: 4, reserved: 0);
        $cartService = app(CartService::class);
        $item = $cartService->addItem($variant->product_id, $variant->id, 2);

        $this->assertSame(2, $this->inventory->snapshot($variant->fresh())['reserved']);

        $cartService->removeItem($item);

        $snapshot = $this->inventory->snapshot($variant->fresh());
        $this->assertSame(0, $snapshot['reserved']);
        $this->assertSame(4, $snapshot['available']);
    }

    public function test_set_on_hand_syncs_variant_aggregate_and_blocks_below_reserved(): void
    {
        [$variant, $item] = $this->createTrackedVariant(onHand: 10, reserved: 4);

        $this->inventory->setOnHand($variant, 8);

        $this->assertSame(8, $item->fresh()->quantity_on_hand);
        $this->assertSame(8, $variant->fresh()->stock_quantity);

        $this->expectException(\InvalidArgumentException::class);
        $this->inventory->setOnHand($variant, 2);
    }

    public function test_low_stock_alerts_use_available_quantity(): void
    {
        [$variant, $item] = $this->createTrackedVariant(onHand: 6, reserved: 4, threshold: 3);

        $alerts = $this->inventory->lowStockAlerts();

        $this->assertTrue(
            $alerts->contains(fn (Inventory $row): bool => $row->product_variant_id === $variant->id),
        );

        $item->update(['quantity_on_hand' => 10, 'quantity_reserved' => 1]);
        $variant->update(['stock_quantity' => 10]);

        $alerts = $this->inventory->lowStockAlerts();

        $this->assertFalse(
            $alerts->contains(fn (Inventory $row): bool => $row->product_variant_id === $variant->id),
        );
    }

    /** @return array{0: ProductVariant, 1: Inventory} */
    private function createTrackedVariant(
        int $onHand,
        int $reserved = 0,
        int $threshold = 5,
    ): array {
        $warehouse = Warehouse::query()->where('is_default', true)->firstOrFail();

        $product = Product::factory()->create([
            'product_type' => 'simple',
            'status' => \App\Enums\ProductStatus::Active,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => $onHand,
            'low_stock_threshold' => $threshold,
            'is_default' => true,
            'is_active' => true,
        ]);

        $product->update(['default_variant_id' => $variant->id]);

        $item = Inventory::updateOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id],
            [
                'quantity_on_hand' => $onHand,
                'quantity_reserved' => $reserved,
                'low_stock_threshold' => $threshold,
            ],
        );

        return [$variant->fresh(), $item];
    }
}
