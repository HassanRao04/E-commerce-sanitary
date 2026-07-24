<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Services\CustomerProfileService;
use Illuminate\Database\Seeder;

class CustomerProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = app(CustomerProfileService::class);

        // Existing storefront customers (role already assigned).
        User::query()
            ->role('customer')
            ->orderBy('id')
            ->each(fn (User $user) => $profiles->ensureForUser($user));

        // Users who placed orders (may lack role/profile).
        $orderUserIds = Order::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        User::query()
            ->whereIn('id', $orderUserIds)
            ->orderBy('id')
            ->each(fn (User $user) => $profiles->ensureForUser($user));

        // Any other non-staff registered users still missing a profile.
        User::query()
            ->whereDoesntHave('roles', function ($query): void {
                $query->whereIn('name', [
                    'super-admin',
                    'admin',
                    'manager',
                    'inventory-staff',
                    'sales-staff',
                    'content-manager',
                ]);
            })
            ->whereDoesntHave('customer')
            ->orderBy('id')
            ->each(fn (User $user) => $profiles->ensureForUser($user));
    }
}
