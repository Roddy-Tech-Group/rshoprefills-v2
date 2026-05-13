<?php

use App\Http\Controllers\Admin\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest admin routes (login)
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store']);
    });

    // Authenticated admin routes
    Route::middleware('admin')->group(function () {
        Route::view('dashboard', 'admin.dashboard')->name('dashboard');
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
    });
});
