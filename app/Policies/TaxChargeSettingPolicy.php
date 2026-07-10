<?php

namespace App\Policies;

use App\Models\TaxChargeSetting;
use App\Models\User;

class TaxChargeSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tax.view');
    }

    public function update(User $user, ?TaxChargeSetting $taxChargeSetting = null): bool
    {
        return $user->can('tax.manage');
    }
}
