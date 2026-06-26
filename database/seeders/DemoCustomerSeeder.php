<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Ahmed Khan', 'email' => 'ahmed@example.com', 'type' => CustomerType::Retail],
            ['name' => 'BuildMart Pvt Ltd', 'email' => 'orders@buildmart.pk', 'type' => CustomerType::Wholesale, 'company' => 'BuildMart Pvt Ltd'],
            ['name' => 'Sanitary World', 'email' => 'dealer@sanitaryworld.pk', 'type' => CustomerType::Dealer, 'company' => 'Sanitary World'],
        ];

        foreach ($customers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => '+92-300-1234567',
                    'status' => 'active',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['customer']);

            Customer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $data['company'] ?? null,
                    'customer_type' => $data['type'],
                    'credit_limit' => $data['type'] === CustomerType::Wholesale ? 500000 : 0,
                ]
            );
        }
    }
}
