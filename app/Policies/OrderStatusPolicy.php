<?php

namespace App\Policies;

use App\Models\OrderStatus;
use App\Models\User;

class OrderStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.workflow.view');
    }

    public function view(User $user, OrderStatus $orderStatus): bool
    {
        return $user->can('orders.workflow.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.workflow.manage');
    }

    public function update(User $user, OrderStatus $orderStatus): bool
    {
        return $user->can('orders.workflow.manage');
    }

    public function delete(User $user, OrderStatus $orderStatus): bool
    {
        return $user->can('orders.workflow.manage') && ! $orderStatus->is_system;
    }
}
