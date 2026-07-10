<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use App\Services\OrderWorkflowService;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'slug' => 'pending',
                'name' => 'Pending',
                'badge_color' => 'amber',
                'sort_order' => 10,
                'is_system' => true,
                'is_default' => true,
                'show_in_progress' => true,
                'customer_group' => 'pending',
            ],
            [
                'slug' => 'confirmed',
                'name' => 'Confirmed',
                'badge_color' => 'blue',
                'sort_order' => 20,
                'is_system' => true,
                'show_in_progress' => true,
                'customer_group' => 'pending',
            ],
            [
                'slug' => 'processing',
                'name' => 'Processing',
                'badge_color' => 'indigo',
                'sort_order' => 30,
                'is_system' => true,
                'show_in_progress' => true,
                'customer_group' => 'processing',
            ],
            [
                'slug' => 'packed',
                'name' => 'Packed',
                'badge_color' => 'violet',
                'sort_order' => 40,
                'is_system' => true,
                'show_in_progress' => true,
                'customer_group' => 'processing',
            ],
            [
                'slug' => 'shipped',
                'name' => 'Shipped',
                'badge_color' => 'cyan',
                'sort_order' => 50,
                'is_system' => true,
                'show_in_progress' => true,
                'customer_group' => 'processing',
            ],
            [
                'slug' => 'delivered',
                'name' => 'Delivered',
                'badge_color' => 'emerald',
                'sort_order' => 60,
                'is_system' => true,
                'is_delivered' => true,
                'is_terminal' => true,
                'show_in_progress' => true,
                'customer_group' => 'delivered',
            ],
            [
                'slug' => 'cancelled',
                'name' => 'Cancelled',
                'badge_color' => 'red',
                'sort_order' => 70,
                'is_system' => true,
                'is_cancellation' => true,
                'is_terminal' => true,
                'customer_group' => 'excluded',
            ],
            [
                'slug' => 'returned',
                'name' => 'Returned',
                'badge_color' => 'orange',
                'sort_order' => 80,
                'is_system' => true,
                'is_return' => true,
                'is_terminal' => true,
                'customer_group' => 'excluded',
            ],
        ];

        foreach ($statuses as $row) {
            OrderStatus::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'badge_color' => $row['badge_color'],
                    'sort_order' => $row['sort_order'],
                    'is_system' => $row['is_system'],
                    'is_default' => $row['is_default'] ?? false,
                    'is_cancellation' => $row['is_cancellation'] ?? false,
                    'is_return' => $row['is_return'] ?? false,
                    'is_delivered' => $row['is_delivered'] ?? false,
                    'is_terminal' => $row['is_terminal'] ?? false,
                    'show_in_progress' => $row['show_in_progress'] ?? false,
                    'customer_group' => $row['customer_group'] ?? null,
                    'is_active' => true,
                ],
            );
        }

        OrderStatus::query()
            ->where('is_default', true)
            ->where('slug', '!=', 'pending')
            ->update(['is_default' => false]);

        app(OrderWorkflowService::class)->clearCache();
    }
}
