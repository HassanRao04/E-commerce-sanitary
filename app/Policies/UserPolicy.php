<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Admin\RoleAssignmentService;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->can('users.update')) {
            return false;
        }

        if ($model->hasRole('super-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return true;
    }

    public function assignRole(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function removeRole(User $user, User $model): bool
    {
        if (! $this->update($user, $model)) {
            return false;
        }

        if ($user->is($model)) {
            return false;
        }

        if ($model->hasRole('super-admin') && app(RoleAssignmentService::class)->isLastSuperAdmin($model)) {
            return false;
        }

        return $model->roles()->exists();
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->can('users.delete')) {
            return false;
        }

        if ($model->hasRole('super-admin')) {
            return false;
        }

        if ($user->is($model)) {
            return false;
        }

        return true;
    }

    public function bulkManage(User $user): bool
    {
        return $user->can('users.update') || $user->can('users.delete');
    }
}
