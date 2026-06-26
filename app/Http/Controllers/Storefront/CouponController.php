<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\RedirectResponse;

class CouponController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
    ) {}

    public function store(ApplyCouponRequest $request): RedirectResponse
    {
        $cart = $this->cartService->resolve();
        $coupon = $this->couponService->apply($cart, $request->string('code')->toString());

        return redirect()
            ->back()
            ->with('success', "Coupon {$coupon->code} applied.");
    }

    public function destroy(): RedirectResponse
    {
        $cart = $this->cartService->resolve();
        $this->couponService->remove($cart);

        return redirect()
            ->back()
            ->with('success', 'Coupon removed.');
    }
}
