<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCascadeAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_deleting_parent_category_logs_audit_for_all_descendants(): void
    {
        $parent = Category::query()->create([
            'name' => 'Faucets & Mixers',
            'slug' => 'faucets-mixers-audit',
            'is_active' => true,
        ]);

        $childA = $parent->children()->create([
            'name' => 'Bathroom Faucets',
            'slug' => 'bathroom-faucets-audit',
            'is_active' => true,
        ]);

        $childB = $parent->children()->create([
            'name' => 'Kitchen Faucets',
            'slug' => 'kitchen-faucets-audit',
            'is_active' => true,
        ]);

        $grandchild = $childA->children()->create([
            'name' => 'Basin Mixers',
            'slug' => 'basin-mixers-audit',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $parent))
            ->assertRedirect(route('admin.categories.index'));

        foreach ([$parent, $childA, $childB, $grandchild] as $category) {
            $this->assertSoftDeleted('categories', ['id' => $category->id]);

            $log = ActivityLog::query()
                ->where('action', 'category.deleted')
                ->where('model_type', Category::class)
                ->where('model_id', $category->id)
                ->first();

            $this->assertNotNull($log, "Missing category.deleted audit for category #{$category->id}");
            $this->assertSame($this->admin->id, $log->user_id);
            $this->assertSame($category->name, $log->old_values['name'] ?? null);
            $this->assertSame(
                $parent->id,
                $log->new_values['cascade_root_category_id'] ?? null,
            );
            $this->assertNotEmpty($log->new_values['cascade_operation_id'] ?? null);
        }

        $operationIds = ActivityLog::query()
            ->where('action', 'category.deleted')
            ->whereIn('model_id', [$parent->id, $childA->id, $childB->id, $grandchild->id])
            ->pluck('new_values')
            ->map(fn (?array $values): ?string => $values['cascade_operation_id'] ?? null)
            ->unique()
            ->filter()
            ->values();

        $this->assertCount(1, $operationIds);
        $this->assertSame('root', ActivityLog::query()
            ->where('model_id', $parent->id)
            ->where('action', 'category.deleted')
            ->value('new_values')['cascade_role'] ?? null);
    }
}
