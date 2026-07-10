<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('activity.view');
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->can('activity.view');
    }
}
