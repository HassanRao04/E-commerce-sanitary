<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('homepage.view');
    }

    public function create(User $user): bool
    {
        return $user->can('homepage.manage');
    }

    public function update(User $user, Banner $banner): bool
    {
        return $user->can('homepage.manage');
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $user->can('homepage.manage');
    }
}
