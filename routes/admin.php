<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CheckoutRulesController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\InfluencerPerformanceController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderWorkflowController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\ShippingSettingsController;
use App\Http\Controllers\Admin\TaxChargeSettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ERP Admin Routes
|--------------------------------------------------------------------------
|
| Prefix: /admin
| Name prefix: admin.*
| Middleware: auth, staff
|
| Structure:
|   Dashboard
|   People      → Users, Customers
|   Catalog     → Products, Categories, Brands, Inventory
|   Commerce    → Orders, Invoices, Payments, Coupons, Shipping
|   Engagement  → Reviews
|   Insights    → Reports
|   System      → Settings
|
*/

Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function (): void {

    // ── Dashboard ────────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('can:dashboard.view')
        ->name('dashboard');

    // ── People ───────────────────────────────────────────────────────────
    Route::middleware('permission:users.create')->group(function (): void {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('influencers/create', [UserController::class, 'createInfluencer'])->name('influencers.create');
        Route::post('influencers', [UserController::class, 'storeInfluencer'])->name('influencers.store');
    });

    Route::middleware('permission:users.view')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('influencers', [UserController::class, 'influencers'])->name('influencers.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware('permission:users.update')->group(function (): void {
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role.update');
        Route::delete('users/{user}/role', [UserController::class, 'destroyRole'])->name('users.role.destroy');
        Route::post('users/bulk', [UserController::class, 'bulkAction'])->name('users.bulk');
        Route::get('influencers/{influencer}/edit', [UserController::class, 'editInfluencer'])->name('influencers.edit');
        Route::put('influencers/{influencer}', [UserController::class, 'updateInfluencer'])->name('influencers.update');
        Route::patch('influencers/{influencer}/activate', [UserController::class, 'activateInfluencer'])->name('influencers.activate');
        Route::patch('influencers/{influencer}/deactivate', [UserController::class, 'deactivateInfluencer'])->name('influencers.deactivate');
    });

    Route::middleware('permission:users.delete')->group(function (): void {
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::delete('influencers/{influencer}', [UserController::class, 'destroyInfluencer'])->name('influencers.destroy');
    });

    Route::middleware('can:customers.view')->group(function (): void {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::middleware('can:customers.manage')->group(function (): void {
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::patch('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        });
    });

    // ── Catalog ──────────────────────────────────────────────────────────
    Route::middleware('can:products.view')->group(function (): void {
        Route::resource('products', ProductController::class)->except(['show']);
        Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])
            ->middleware('can:products.update')
            ->name('products.images.destroy');
    });

    Route::middleware('can:categories.view')->group(function (): void {
        Route::resource('categories', CategoryController::class)->except(['show']);
    });

    Route::middleware('can:brands.view')->group(function (): void {
        Route::resource('brands', BrandController::class)->except(['show']);
    });

    Route::middleware('can:inventory.view')->group(function (): void {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::patch('inventory/{inventory}/adjust', [InventoryController::class, 'adjust'])
            ->middleware('can:inventory.manage')
            ->name('inventory.adjust');
    });

    // ── Commerce ─────────────────────────────────────────────────────────
    Route::middleware('can:orders.view')->group(function (): void {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/workflow', [OrderWorkflowController::class, 'index'])->name('orders.workflow.index');
        Route::post('orders/workflow', [OrderWorkflowController::class, 'store'])
            ->middleware('can:orders.workflow.manage')
            ->name('orders.workflow.store');
        Route::patch('orders/workflow/{orderStatus}', [OrderWorkflowController::class, 'update'])
            ->middleware('can:orders.workflow.manage')
            ->name('orders.workflow.update');
        Route::delete('orders/workflow/{orderStatus}', [OrderWorkflowController::class, 'destroy'])
            ->middleware('can:orders.workflow.manage')
            ->name('orders.workflow.destroy');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/track', [OrderController::class, 'track'])->name('orders.track');
        Route::get('orders/{order}/invoice/print', [OrderController::class, 'printInvoice'])
            ->middleware('can:billing.view')
            ->name('orders.invoice.print');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->middleware('can:orders.update')
            ->name('orders.update-status');
        Route::patch('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])
            ->middleware('can:orders.update')
            ->name('orders.update-payment-status');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->middleware('can:orders.cancel')
            ->name('orders.cancel');
        Route::post('orders/{order}/invoice', [OrderController::class, 'generateInvoice'])
            ->middleware('can:billing.manage')
            ->name('orders.invoice.generate');
    });

    Route::middleware('can:billing.view')->group(function (): void {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::middleware('can:billing.manage')->group(function (): void {
            Route::patch('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
            Route::patch('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
            Route::patch('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        });
    });

    Route::middleware('can:payments.view')->group(function (): void {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    });

    Route::middleware('can:coupons.view')->group(function (): void {
        Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::get('influencer-performance', [InfluencerPerformanceController::class, 'index'])
            ->name('influencer-performance.index');
        Route::get('influencer-performance/{influencer}', [InfluencerPerformanceController::class, 'show'])
            ->name('influencer-performance.show');
        Route::get('influencer-performance/{influencer}/export/{format}', [InfluencerPerformanceController::class, 'export'])
            ->whereIn('format', ['csv', 'excel'])
            ->name('influencer-performance.export');
        Route::middleware('can:coupons.manage')->group(function (): void {
            Route::patch('influencer-performance/{influencer}/orders/{order}/mark-paid', [InfluencerPerformanceController::class, 'markPaid'])
                ->name('influencer-performance.mark-paid');
            Route::post('influencer-performance/{influencer}/orders/{order}/pay', [InfluencerPerformanceController::class, 'payCommission'])
                ->name('influencer-performance.pay-commission');
            Route::post('influencer-performance/{influencer}/orders/mark-paid', [InfluencerPerformanceController::class, 'markSelectedPaid'])
                ->name('influencer-performance.mark-selected-paid');
            Route::post('influencer-performance/{influencer}/payouts', [InfluencerPerformanceController::class, 'recordPayout'])
                ->name('influencer-performance.payout');
            Route::get('coupons/create', [CouponController::class, 'create'])->name('coupons.create');
            Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
            Route::get('coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
            Route::put('coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
            Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
        });
    });

    Route::middleware('can:checkout_rules.view')->group(function (): void {
        Route::get('checkout/rules', [CheckoutRulesController::class, 'edit'])->name('checkout.rules.edit');
        Route::patch('checkout/rules', [CheckoutRulesController::class, 'update'])
            ->middleware('can:checkout_rules.manage')
            ->name('checkout.rules.update');
    });

    Route::middleware('can:tax.view')->group(function (): void {
        Route::get('tax/settings', [TaxChargeSettingsController::class, 'edit'])->name('tax.settings.edit');
        Route::patch('tax/settings', [TaxChargeSettingsController::class, 'update'])
            ->middleware('can:tax.manage')
            ->name('tax.settings.update');
    });

    Route::middleware('can:shipping.view')->group(function (): void {
        Route::get('shipping/settings', [ShippingSettingsController::class, 'edit'])->name('shipping.settings.edit');
        Route::get('shipping/settings/products/search', [ShippingSettingsController::class, 'searchProducts'])
            ->name('shipping.settings.products.search');
        Route::get('shipping', [ShippingController::class, 'index'])->name('shipping.index');
        Route::get('shipping/{shipping}', [ShippingController::class, 'show'])->name('shipping.show');
        Route::get('shipping/{shipping}/label', [ShippingController::class, 'printLabel'])
            ->middleware('can:shipping.view')
            ->name('shipping.label');
        Route::patch('shipping/settings', [ShippingSettingsController::class, 'update'])
            ->middleware('can:shipping.manage')
            ->name('shipping.settings.update');
        Route::post('orders/{order}/shipping', [ShippingController::class, 'store'])
            ->middleware('can:shipping.manage')
            ->name('orders.shipping.store');
        Route::patch('shipping/{shipping}', [ShippingController::class, 'update'])
            ->middleware('can:shipping.manage')
            ->name('shipping.update');
        Route::post('shipping/{shipping}/events', [ShippingController::class, 'storeEvent'])
            ->middleware('can:shipping.manage')
            ->name('shipping.events.store');
    });

    // ── Engagement ───────────────────────────────────────────────────────
    Route::middleware('can:notifications.view')->group(function (): void {
        Route::get('contact-messages', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('contact-messages/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
        Route::patch('contact-messages/{inquiry}/status', [InquiryController::class, 'updateStatus'])
            ->middleware('can:notifications.manage')
            ->name('inquiries.status');
        Route::delete('contact-messages/{inquiry}', [InquiryController::class, 'destroy'])
            ->middleware('can:notifications.manage')
            ->name('inquiries.destroy');
        Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])
            ->name('notifications.open');
    });

    Route::middleware('can:reviews.view')->group(function (): void {
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('reviews/settings', [ReviewController::class, 'settings'])->name('reviews.settings');
        Route::patch('reviews/settings', [ReviewController::class, 'updateSettings'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.settings.update');
        Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.approve');
        Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.reject');
        Route::patch('reviews/{review}/hide', [ReviewController::class, 'hide'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.hide');
        Route::patch('reviews/{review}/feature', [ReviewController::class, 'toggleFeatured'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.feature');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.destroy');
    });

    // ── Insights ─────────────────────────────────────────────────────────
    Route::middleware('can:reports.view')->group(function (): void {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{type}/export/{format}', [ReportController::class, 'export'])
            ->whereIn('format', ['csv', 'excel', 'pdf'])
            ->name('reports.export');
    });

    // ── System ───────────────────────────────────────────────────────────
    Route::middleware('can:activity.view')->group(function (): void {
        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });

    Route::middleware('permission:homepage.view')->group(function (): void {
        Route::get('homepage', [HomepageController::class, 'index'])->name('homepage.index');
        Route::get('homepage/products/search', [HomepageController::class, 'searchProducts'])->name('homepage.products.search');
    });

    Route::middleware('permission:homepage.manage')->group(function (): void {
        Route::patch('homepage/branding', [HomepageController::class, 'updateBranding'])->name('homepage.branding.update');
        Route::patch('homepage/sections', [HomepageController::class, 'updateSections'])->name('homepage.sections.update');
        Route::patch('homepage/footer', [HomepageController::class, 'updateFooter'])->name('homepage.footer.update');
        Route::patch('homepage/contact', [HomepageController::class, 'updateContact'])->name('homepage.contact.update');
        Route::patch('homepage/header', [HomepageController::class, 'updateHeader'])->name('homepage.header.update');
        Route::patch('homepage/social', [HomepageController::class, 'updateSocial'])->name('homepage.social.update');
        Route::get('homepage/hero/create', [HomepageController::class, 'createHero'])->name('homepage.hero.create');
        Route::post('homepage/hero', [HomepageController::class, 'storeHero'])->name('homepage.hero.store');
        Route::get('homepage/hero/{banner}/edit', [HomepageController::class, 'editHero'])->name('homepage.hero.edit');
        Route::put('homepage/hero/{banner}', [HomepageController::class, 'updateHero'])->name('homepage.hero.update');
        Route::delete('homepage/hero/{banner}', [HomepageController::class, 'destroyHero'])->name('homepage.hero.destroy');
    });

    Route::middleware('can:settings.view')->group(function (): void {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('settings', [SettingsController::class, 'update'])
            ->middleware('can:settings.manage')
            ->name('settings.update');
    });
});
