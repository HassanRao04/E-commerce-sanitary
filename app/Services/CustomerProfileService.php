<?php

namespace App\Services;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\User;

class CustomerProfileService
{
    /**
     * Ensure a customer_profiles row exists for the given user.
     * Idempotent — never creates duplicates.
     */
    public function ensureForUser(User $user): Customer
    {
        if (! $user->hasRole('customer') && ! $user->isStaff()) {
            $user->assignRole('customer');
        }

        return Customer::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'customer_type' => CustomerType::Retail,
                'credit_limit' => 0,
                'lifetime_spend' => 0,
            ]
        );
    }
}
