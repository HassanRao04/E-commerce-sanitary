<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notifications.view');
    }

    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->can('notifications.view');
    }

    public function update(User $user, Inquiry $inquiry): bool
    {
        return $user->can('notifications.manage');
    }

    public function delete(User $user, Inquiry $inquiry): bool
    {
        return $user->can('notifications.manage');
    }
}
