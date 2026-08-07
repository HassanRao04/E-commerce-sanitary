<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CheckoutRulesSetting;
use App\Models\Coupon;
use App\Models\CourierProvider;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shipping;
use App\Models\ShippingSetting;
use App\Models\SiteSetting;
use App\Models\TaxChargeSetting;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\BannerPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CheckoutRulesSettingPolicy;
use App\Policies\CouponPolicy;
use App\Policies\CourierProviderPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\InquiryPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ShippingPolicy;
use App\Policies\ShippingSettingPolicy;
use App\Policies\SiteSettingPolicy;
use App\Policies\TaxChargeSettingPolicy;
use App\Policies\UserPolicy;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ShippingRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\BrandRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\InventoryRepository;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\ShippingRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\CartService;
use App\Services\CheckoutRulesEngine;
use App\Services\Storefront\StorefrontContentService;
use App\Services\WishlistService;
use App\Events\OrderPlaced;
use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;
use App\Listeners\LogUserPasswordReset;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\SendOrderWhatsAppNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(BrandRepositoryInterface::class, BrandRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(ShippingRepositoryInterface::class, ShippingRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Banner::class, BannerPolicy::class);
        Gate::policy(Inventory::class, InventoryPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(CourierProvider::class, CourierProviderPolicy::class);
        Gate::policy(CheckoutRulesSetting::class, CheckoutRulesSettingPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Inquiry::class, InquiryPolicy::class);
        Gate::policy(SiteSetting::class, SiteSettingPolicy::class);
        Gate::policy(Shipping::class, ShippingPolicy::class);
        Gate::policy(ShippingSetting::class, ShippingSettingPolicy::class);
        Gate::policy(TaxChargeSetting::class, TaxChargeSettingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        Event::listen(Login::class, LogUserLogin::class);
        Event::listen(Logout::class, LogUserLogout::class);
        Event::listen(PasswordReset::class, LogUserPasswordReset::class);
        Event::listen(OrderPlaced::class, SendOrderConfirmationEmail::class);
        Event::listen(OrderPlaced::class, SendOrderWhatsAppNotification::class);

        View::composer(['layouts.storefront', 'components.storefront.*'], function ($view): void {
            $content = app(StorefrontContentService::class);

            $view->with('checkoutRules', app(CheckoutRulesEngine::class)->storefrontContext());
            $view->with('storefrontFooter', $content->footer());
            $view->with('storefrontContact', $content->contact());
            $view->with('heroSlides', $content->heroSlides());
        });

        View::composer('layouts.storefront', function ($view): void {
            $cartService = app(CartService::class);

            $view->with('cartItemCount', $cartService->itemCount());
            $view->with('wishlistItemCount', app(WishlistService::class)->itemCount());
            $view->with('headerCartPreview', $cartService->preview());
            $view->with('headerNavCategories', Category::query()
                ->active()
                ->roots()
                ->ordered()
                ->with(['children' => fn ($query) => $query->active()->ordered()->limit(8)])
                ->limit(8)
                ->get());
            $view->with('headerNavBrands', Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(6)
                ->get());
            $view->with('storefrontHeader', \App\Support\StorefrontHeader::resolved());
        });
    }
}
