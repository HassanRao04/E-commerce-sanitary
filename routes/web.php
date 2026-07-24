<?php

use App\Http\Controllers\Influencer\CommissionController as InfluencerCommissionController;
use App\Http\Controllers\Influencer\DashboardController as InfluencerDashboardController;
use App\Http\Controllers\Influencer\OrderController as InfluencerOrderController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/media/placeholder', [MediaController::class, 'placeholder'])->name('media.placeholder');

require __DIR__.'/storefront.php';

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user?->isStaff()) {
        return redirect()->route('admin.dashboard');
    }

    if ($user?->isInfluencer()) {
        return redirect()->route('influencer.dashboard');
    }

    return redirect()->route('shop.account.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('influencer')->name('influencer.')->group(function (): void {
    Route::get('/', InfluencerDashboardController::class)->name('dashboard');
    Route::get('/orders', [InfluencerOrderController::class, 'index'])->name('orders.index');
    Route::get('/commissions', [InfluencerCommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/export/{format}', [InfluencerCommissionController::class, 'export'])
        ->whereIn('format', ['csv', 'excel'])
        ->name('commissions.export');
    Route::get('/wallet', [InfluencerCommissionController::class, 'wallet'])->name('wallet');
});

require __DIR__.'/admin.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
