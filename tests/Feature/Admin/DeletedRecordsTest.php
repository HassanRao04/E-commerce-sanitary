<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletedRecordsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', config('shop.admin_email'))->firstOrFail();

        $this->admin = User::factory()->create([
            'email' => 'records.admin@example.com',
            'name' => 'Records Admin',
        ]);
        $this->admin->syncRoles(['admin']);
    }

    public function test_authorized_user_can_soft_delete_product(): void
    {
        $product = Product::firstOrFail();

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_deleted_product_disappears_from_normal_queries(): void
    {
        $product = Product::firstOrFail();
        $productId = $product->id;

        $product->delete();

        $this->assertNull(Product::query()->find($productId));
        $this->assertNotNull(Product::withTrashed()->find($productId));
    }

    public function test_deleted_product_appears_in_deleted_records(): void
    {
        $product = Product::firstOrFail();
        $product->delete();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.deleted-records.index'));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee('#'.$product->id);
    }

    public function test_unauthorized_user_cannot_restore(): void
    {
        $product = Product::firstOrFail();
        $product->delete();

        $this->actingAs($this->admin)
            ->post(route('admin.deleted-records.restore', ['type' => 'product', 'id' => $product->id]))
            ->assertForbidden();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_authorized_administrator_can_restore_product(): void
    {
        $product = Product::firstOrFail();
        $productId = $product->id;

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($this->superAdmin)
            ->post(route('admin.deleted-records.restore', ['type' => 'product', 'id' => $productId]))
            ->assertRedirect(route('admin.deleted-records.index'));

        $this->assertNotSoftDeleted('products', ['id' => $productId]);
        $this->assertNotNull(Product::query()->find($productId));
    }

    public function test_deletion_creates_audit_log(): void
    {
        $product = Product::firstOrFail();

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.products.destroy', $product));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'product.deleted',
            'model_type' => Product::class,
            'model_id' => $product->id,
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_restoration_creates_audit_log(): void
    {
        $product = Product::firstOrFail();
        $productId = $product->id;

        $this->actingAs($this->superAdmin)->delete(route('admin.products.destroy', $product));

        $this->actingAs($this->superAdmin)
            ->post(route('admin.deleted-records.restore', ['type' => 'product', 'id' => $productId]));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'product.restored',
            'model_type' => Product::class,
            'model_id' => $productId,
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_deleted_record_retains_its_id(): void
    {
        $product = Product::firstOrFail();
        $originalId = $product->id;

        $product->delete();

        $trashed = Product::withTrashed()->findOrFail($originalId);
        $this->assertSame($originalId, $trashed->id);
    }

    public function test_deleted_records_page_has_no_force_delete_action(): void
    {
        $product = Product::firstOrFail();
        $product->delete();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.deleted-records.index'));

        $response->assertOk();
        $response->assertSee('Restore');
        $response->assertDontSee('Force Delete');
        $response->assertDontSee('Delete Permanently');
        $response->assertDontSee('Purge');
    }

    public function test_immutable_entities_are_not_recoverable(): void
    {
        $recoverableModels = collect(config('recoverable.entities'))
            ->pluck('model')
            ->all();

        $this->assertNotContains(Order::class, $recoverableModels);
        $this->assertNotContains(Payment::class, $recoverableModels);
        $this->assertNotContains(\App\Models\Invoice::class, $recoverableModels);
    }

    public function test_coupon_deletion_audit_preserves_model_reference(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'AUDITCOUPON']);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.coupons.destroy', $coupon));

        $log = ActivityLog::query()
            ->where('action', 'coupon.deleted')
            ->where('model_id', $coupon->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(Coupon::class, $log->model_type);
        $this->assertSame('AUDITCOUPON', $log->old_values['code'] ?? null);
    }

    public function test_admin_with_records_view_can_open_deleted_records(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.deleted-records.index'));

        $response->assertOk();
    }

    public function test_staff_without_records_view_cannot_open_deleted_records(): void
    {
        $manager = User::factory()->create(['email' => 'manager.records@example.com']);
        $manager->syncRoles(['manager']);

        $this->actingAs($manager)
            ->get(route('admin.deleted-records.index'))
            ->assertForbidden();
    }
}
