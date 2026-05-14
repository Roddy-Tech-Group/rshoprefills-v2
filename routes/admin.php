<?php

use App\Http\Controllers\Admin\AdminCatalogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFintechController;
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

        // Admin Dashboard API
        Route::prefix('api/dashboard')->name('api.dashboard.')->group(function () {
            Route::get('overview', [AdminDashboardController::class, 'overview'])->name('overview');
            Route::get('revenue-chart', [AdminDashboardController::class, 'revenueChart'])->name('revenue-chart');
            Route::get('latest-users', [AdminDashboardController::class, 'latestUsers'])->name('latest-users');
            Route::get('latest-transactions', [AdminDashboardController::class, 'latestTransactions'])->name('latest-transactions');
        });

        // Admin Fintech Monitoring API
        Route::prefix('api/monitoring')->name('api.monitoring.')->group(function () {
            Route::get('transactions', [AdminFintechController::class, 'transactions'])->name('transactions');
            Route::get('fundings', [AdminFintechController::class, 'fundings'])->name('fundings');
            Route::get('wallets', [AdminFintechController::class, 'wallets'])->name('wallets');
        });

        // Admin Catalog API
        Route::prefix('api/catalog')->name('api.catalog.')->group(function () {
            Route::post('sync/zendit', [AdminCatalogController::class, 'syncZendit'])->name('sync.zendit');
            Route::get('products', [AdminCatalogController::class, 'products'])->name('products');
            Route::patch('products/{product}/toggle-active', [AdminCatalogController::class, 'toggleActive']);
            Route::patch('products/{product}/toggle-featured', [AdminCatalogController::class, 'toggleFeatured']);
            Route::patch('products/{product}/toggle-popular', [AdminCatalogController::class, 'togglePopular']);
        });
    });
});
