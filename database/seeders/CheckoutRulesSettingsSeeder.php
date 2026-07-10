<?php

namespace Database\Seeders;

use App\Models\CheckoutRulesSetting;
use Illuminate\Database\Seeder;

class CheckoutRulesSettingsSeeder extends Seeder
{
    public function run(): void
    {
        CheckoutRulesSetting::current();
    }
}
