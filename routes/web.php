<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/media/placeholder', [MediaController::class, 'placeholder'])->name('media.placeholder');

require __DIR__.'/storefront.php';

Route::get('/dashboard', function () {
    if (auth()->user()?->isStaff()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('shop.account.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/admin.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
