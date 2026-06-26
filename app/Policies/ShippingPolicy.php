<?php

namespace App\Policies;

use App\Models\Shipping;
use App\Models\User;

class ShippingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shipping.view');
    }

    public function view(User $user, Shipping $shipping): bool
    {
        return $user->can('shipping.view');
    }

    public function create(User $user): bool
    {
        return $user->can('shipping.manage');
    }

    public function update(User $user, Shipping $shipping): bool
    {
        return $user->can('shipping.manage');
    }
}
