<?php

namespace App\Policies;

use App\Models\CheckoutRulesSetting;
use App\Models\User;

class CheckoutRulesSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('checkout_rules.view');
    }

    public function update(User $user, ?CheckoutRulesSetting $checkoutRulesSetting = null): bool
    {
        return $user->can('checkout_rules.manage');
    }
}
