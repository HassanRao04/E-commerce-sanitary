<?php

namespace App\Policies;

use App\Models\ShippingSetting;
use App\Models\User;

class ShippingSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shipping.view');
    }

    public function update(User $user, ?ShippingSetting $shippingSetting = null): bool
    {
        return $user->can('shipping.manage');
    }
}
