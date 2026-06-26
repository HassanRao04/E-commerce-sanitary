<?php

use App\Http\Controllers\Storefront\AccountAddressController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CouponController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\OrderTrackingController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

Route::name('shop.')->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/shop', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products', fn () => redirect()->route('shop.products.index', request()->query(), 301));
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'storeContact'])->name('contact.store');

    Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');

    Route::get('/track-order', [OrderTrackingController::class, 'index'])->name('orders.track');
    Route::post('/track-order', [OrderTrackingController::class, 'show'])->name('orders.track.show');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    Route::middleware('auth')->prefix('account')->name('account.')->group(function (): void {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{order}', [AccountController::class, 'orderShow'])->name('orders.show');
        Route::get('/orders/{order}/track', [AccountController::class, 'orderTrack'])->name('orders.track');
        Route::get('/orders/{order}/invoice', [AccountController::class, 'downloadInvoice'])->name('orders.invoice');

        Route::get('/addresses', [AccountAddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/create', [AccountAddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses', [AccountAddressController::class, 'store'])->name('addresses.store');
        Route::get('/addresses/{address}/edit', [AccountAddressController::class, 'edit'])->name('addresses.edit');
        Route::put('/addresses/{address}', [AccountAddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AccountAddressController::class, 'destroy'])->name('addresses.destroy');
    });

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/preview', [CartController::class, 'preview'])->name('cart.preview');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::post('/cart/coupon', [CouponController::class, 'store'])->name('cart.coupon.apply');
    Route::delete('/cart/coupon', [CouponController::class, 'destroy'])->name('cart.coupon.remove');

    Route::middleware('auth')->group(function (): void {
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
        Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('payment.show');
    });
});
