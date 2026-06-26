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
            'roles.view', 'roles.manage',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.manage',
            'brands.view', 'brands.manage',
            'inventory.view', 'inventory.manage',
            'orders.view', 'orders.update', 'orders.cancel',
            'customers.view', 'customers.manage',
            'billing.view', 'billing.manage',
            'payments.view', 'payments.manage',
            'shipping.view', 'shipping.manage',
            'coupons.view', 'coupons.manage',
            'reviews.view', 'reviews.moderate',
            'reports.view',
            'settings.view', 'settings.manage',
            'notifications.view', 'notifications.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $allPermissions = Permission::all();

        $roles = [
            'super-admin' => $allPermissions->pluck('name')->all(),
            'admin' => $permissions,
            'manager' => [
                'dashboard.view',
                'products.view', 'products.create', 'products.update',
                'categories.view', 'categories.manage',
                'brands.view', 'brands.manage',
                'inventory.view', 'inventory.manage',
                'orders.view', 'orders.update', 'orders.cancel',
                'customers.view', 'customers.manage',
                'billing.view', 'billing.manage',
                'payments.view', 'payments.manage',
                'shipping.view', 'shipping.manage',
                'coupons.view', 'coupons.manage',
                'reviews.view', 'reviews.moderate',
                'reports.view',
                'notifications.view',
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
            ],
            'content-manager' => [
                'dashboard.view',
                'products.view', 'products.create', 'products.update',
                'categories.view', 'categories.manage',
                'brands.view', 'brands.manage',
                'reviews.view', 'reviews.moderate',
                'settings.view',
            ],
            'customer' => [],
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
