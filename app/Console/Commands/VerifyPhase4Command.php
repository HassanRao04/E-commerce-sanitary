<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\CouponService;
use App\Services\PaymentService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class VerifyPhase4Command extends Command
{
    protected $signature = 'erp:verify-phase4';

    protected $description = 'Verify Phase 4 storefront, checkout, payments, and coupons';

    public function handle(): int
    {
        $checks = [
            'Storefront home route' => Route::has('shop.home'),
            'Checkout route' => Route::has('shop.checkout.index'),
            'Cart service' => class_exists(CartService::class),
            'Checkout service' => class_exists(CheckoutService::class),
            'Coupon service' => class_exists(CouponService::class),
            'Payment service' => class_exists(PaymentService::class),
            'Payment gateways' => count(app(PaymentGatewayManager::class)->enabled()) > 0,
            'Active products' => Product::query()->active()->exists(),
            'Demo coupons' => Coupon::query()->whereIn('code', ['WELCOME10', 'FLAT500'])->count() >= 2,
            'Admin coupon routes' => Route::has('admin.coupons.store'),
        ];

        $failed = false;

        foreach ($checks as $label => $passed) {
            if ($passed) {
                $this->info("✓ {$label}");
            } else {
                $this->error("✗ {$label}");
                $failed = true;
            }
        }

        if ($failed) {
            $this->newLine();
            $this->warn('Run: php artisan migrate --seed && npm run build');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Phase 4 verification passed.');
        $this->line('Storefront: '.url('/'));
        $this->line('Checkout demo coupon: WELCOME10');

        return self::SUCCESS;
    }
}
