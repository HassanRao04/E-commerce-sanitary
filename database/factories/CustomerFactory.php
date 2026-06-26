<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => null,
            'tax_number' => null,
            'customer_type' => CustomerType::Retail,
            'credit_limit' => 0,
            'lifetime_spend' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Customer $customer): void {
            if ($customer->user && ! $customer->user->hasRole('customer')) {
                $customer->user->assignRole('customer');
            }
        });
    }

    public function wholesale(): static
    {
        return $this->state(fn (): array => [
            'customer_type' => CustomerType::Wholesale,
            'company_name' => fake()->company(),
            'credit_limit' => 500000,
        ]);
    }

    public function dealer(): static
    {
        return $this->state(fn (): array => [
            'customer_type' => CustomerType::Dealer,
            'company_name' => fake()->company(),
        ]);
    }
}
