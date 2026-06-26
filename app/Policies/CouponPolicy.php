<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupons.view');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coupons.manage');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.manage');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.manage');
    }
}
