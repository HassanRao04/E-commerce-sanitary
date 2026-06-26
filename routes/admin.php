<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingController;
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
    Route::middleware('can:users.view')->group(function (): void {
        Route::resource('users', UserController::class)->only(['index', 'create', 'edit']);
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
        Route::middleware('can:coupons.manage')->group(function (): void {
            Route::get('coupons/create', [CouponController::class, 'create'])->name('coupons.create');
            Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
            Route::get('coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
            Route::put('coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
            Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
        });
    });

    Route::middleware('can:shipping.view')->group(function (): void {
        Route::get('shipping', [ShippingController::class, 'index'])->name('shipping.index');
        Route::get('shipping/{shipping}', [ShippingController::class, 'show'])->name('shipping.show');
        Route::get('shipping/{shipping}/label', [ShippingController::class, 'printLabel'])
            ->middleware('can:shipping.view')
            ->name('shipping.label');
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
    Route::middleware('can:reviews.view')->group(function (): void {
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.approve');
        Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject'])
            ->middleware('can:reviews.moderate')
            ->name('reviews.reject');
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
    Route::middleware('can:settings.view')->group(function (): void {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('settings', [SettingsController::class, 'update'])
            ->middleware('can:settings.manage')
            ->name('settings.update');
    });
});
