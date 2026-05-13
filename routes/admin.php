<?php

use App\Http\Controllers\Admin\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest admin routes (login)
    Route::middleware('guest:admin')->group(function () {
        Volt::route('login', 'admin.auth.login')->name('login');
        Route::post('login', [AdminLoginController::class, 'store']);
    });

    // Authenticated admin routes
    Route::middleware('admin')->group(function () {
        Route::view('dashboard', 'admin.dashboard')->name('dashboard');
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
    });
});
