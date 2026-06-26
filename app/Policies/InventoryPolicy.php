<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        return $user->can('inventory.view');
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return $user->can('inventory.manage');
    }
}
