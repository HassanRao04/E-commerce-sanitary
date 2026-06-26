<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerProfileSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::role('customer')->get();

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['user_id' => $customer->id],
                [
                    'customer_type' => CustomerType::Retail,
                    'credit_limit' => 0,
                    'lifetime_spend' => 0,
                ]
            );
        }
    }
}
