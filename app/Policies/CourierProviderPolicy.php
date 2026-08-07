<?php

namespace App\Policies;

use App\Models\CourierProvider;
use App\Models\User;

class CourierProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shipping.view');
    }

    public function view(User $user, CourierProvider $courierProvider): bool
    {
        return $user->can('shipping.view');
    }

    public function create(User $user): bool
    {
        return $user->can('shipping.manage');
    }

    public function update(User $user, CourierProvider $courierProvider): bool
    {
        return $user->can('shipping.manage');
    }

    public function delete(User $user, CourierProvider $courierProvider): bool
    {
        return $user->can('shipping.manage');
    }
}
