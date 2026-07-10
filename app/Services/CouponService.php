<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutRulesEngine $rulesEngine,
        private readonly CheckoutRulesSettingsService $checkoutRulesSettings,
    ) {}

    public function apply(Cart $cart, string $code): Coupon
    {
        if (! $this->checkoutRulesSettings->couponsEnabled()) {
            throw ValidationException::withMessages([
                'code' => 'Coupons are not available at this time.',
            ]);
        }

        $coupon = Coupon::query()->valid()->byCode($code)->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'code' => 'This coupon is invalid or has expired.',
            ]);
        }

        $cart->load('items');
        $subtotal = $this->rulesEngine->cartSubtotal($cart);
        $discount = $coupon->calculateDiscount($subtotal);

        if ($discount <= 0) {
            $message = $coupon->min_order_amount
                ? 'Minimum order amount of '.config('shop.currency_symbol').number_format((float) $coupon->min_order_amount, 2).' required.'
                : 'This coupon cannot be applied to your cart.';

            throw ValidationException::withMessages(['code' => $message]);
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $coupon->fresh();
    }

    public function remove(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
    }
}
