<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'activity.view',
            'records.view', 'records.restore',
            'roles.view', 'roles.manage',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.manage',
            'brands.view', 'brands.manage',
            'inventory.view', 'inventory.manage',
            'orders.view', 'orders.update', 'orders.cancel', 'orders.workflow.view', 'orders.workflow.manage',
            'customers.view', 'customers.manage',
            'billing.view', 'billing.manage',
            'payments.view', 'payments.manage',
            'shipping.view', 'shipping.manage',
            'tax.view', 'tax.manage',
            'coupons.view', 'coupons.manage',
            'checkout_rules.view', 'checkout_rules.manage',
            'reviews.view', 'reviews.moderate',
            'reports.view',
            'settings.view', 'settings.manage',
            'homepage.view', 'homepage.manage',
            'notifications.view', 'notifications.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $allPermissions = Permission::all();

        $adminPermissions = array_values(array_diff($permissions, [
            'users.delete',
            'records.restore',
        ]));

        $roles = [
            'super-admin' => $allPermissions->pluck('name')->all(),
            'admin' => $adminPermissions,
            'manager' => [
                'dashboard.view',
                'users.view',
                'homepage.view',
                'products.view', 'products.create', 'products.update',
                'categories.view', 'categories.manage',
                'brands.view', 'brands.manage',
                'inventory.view', 'inventory.manage',
                'orders.view', 'orders.update', 'orders.cancel', 'orders.workflow.view', 'orders.workflow.manage', 'orders.workflow.view', 'orders.workflow.manage',
                'customers.view', 'customers.manage',
                'billing.view', 'billing.manage',
                'payments.view', 'payments.manage',
                'shipping.view', 'shipping.manage',
                'tax.view', 'tax.manage',
                'coupons.view', 'coupons.manage',
                'checkout_rules.view', 'checkout_rules.manage',
                'reviews.view', 'reviews.moderate',
                'reports.view',
                'notifications.view', 'notifications.manage',
            ],
            'inventory-staff' => [
                'dashboard.view',
                'products.view',
                'inventory.view', 'inventory.manage',
                'orders.view',
                'shipping.view',
            ],
            'sales-staff' => [
                'dashboard.view',
                'orders.view', 'orders.update',
                'customers.view', 'customers.manage',
                'billing.view',
                'payments.view',
                'coupons.view',
                'reports.view',
                'notifications.view',
            ],
            'content-manager' => [
                'dashboard.view',
                'homepage.view', 'homepage.manage',
                'products.view', 'products.create', 'products.update',
                'categories.view', 'categories.manage',
                'brands.view', 'brands.manage',
                'reviews.view', 'reviews.moderate',
                'settings.view',
                'notifications.view', 'notifications.manage',
            ],
            'customer' => [],
            'influencer' => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($roleName === 'super-admin') {
                $role->syncPermissions($allPermissions);
            } else {
                $role->syncPermissions($rolePermissions);
            }
        }
    }
}
