<?php

use App\Http\Controllers\Admin\AdminCatalogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFintechController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

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

        // Admin content views — read-only Blade pages backed by shipped models.
        // Replace with controllers when CRUD/actions ship.
        Route::view('products', 'admin.products')->name('products');
        Route::view('orders', 'admin.orders')->name('orders');
        Route::view('customers', 'admin.customers')->name('customers');
        Route::view('transactions', 'admin.transactions')->name('transactions');
        Route::view('wallets', 'admin.wallets')->name('wallets');
        Volt::route('rates', 'admin.rates')->name('rates');
        Volt::route('account', 'admin.account')->name('account');

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

            // Hardened Wallet Funding & Financial Operations Extensions
            Route::get('payment-attempts', [AdminFintechController::class, 'paymentAttempts'])->name('payment-attempts');
            Route::get('payment-webhooks', [AdminFintechController::class, 'paymentWebhooks'])->name('payment-webhooks');
            Route::get('reconciliation/pending', [AdminFintechController::class, 'pendingReconciliations'])->name('reconciliation.pending');
            Route::post('reconciliation/{id}/retry', [AdminFintechController::class, 'retryReconciliation'])->name('reconciliation.retry');
            Route::get('metrics', [AdminFintechController::class, 'metrics'])->name('metrics');
        });

        // Admin Commerce Monitoring & Actions API
        Route::prefix('api/commerce')->name('api.commerce.')->group(function () {
            Route::get('orders', [\App\Http\Controllers\Admin\AdminCommerceController::class, 'listOrders'])->name('orders');
            Route::get('payments', [\App\Http\Controllers\Admin\AdminCommerceController::class, 'listPayments'])->name('payments');
            Route::get('fulfillments', [\App\Http\Controllers\Admin\AdminCommerceController::class, 'listFulfillmentLogs'])->name('fulfillments');
            Route::post('orders/{itemId}/retry-fulfillment', [\App\Http\Controllers\Admin\AdminCommerceController::class, 'retryFulfillment'])->name('retry-fulfillment');
            Route::post('orders/{orderId}/refund', [\App\Http\Controllers\Admin\AdminCommerceController::class, 'refundOrder'])->name('refund');
        });

        // Admin Catalog API
        Route::prefix('api/catalog')->name('api.catalog.')->group(function () {
            Route::post('sync/zendit', [AdminCatalogController::class, 'syncZendit'])->name('sync.zendit');
            Route::post('sync/zendit-esims', [AdminCatalogController::class, 'syncZenditEsims'])->name('sync.zendit-esims');
            Route::get('products', [AdminCatalogController::class, 'products'])->name('products');
            Route::patch('products/{product}/toggle-active', [AdminCatalogController::class, 'toggleActive']);
            Route::patch('products/{product}/toggle-featured', [AdminCatalogController::class, 'toggleFeatured']);
            Route::patch('products/{product}/toggle-popular', [AdminCatalogController::class, 'togglePopular']);
        });

        // Admin Notifications & Compliance API
        Route::prefix('api/notifications')->name('api.notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NotificationAdminApiController::class, 'index'])->name('index');
            Route::get('deliveries', [\App\Http\Controllers\Admin\NotificationAdminApiController::class, 'deliveries'])->name('deliveries');
            Route::get('metrics', [\App\Http\Controllers\Admin\NotificationAdminApiController::class, 'metrics'])->name('metrics');
            Route::post('{id}/retry', [\App\Http\Controllers\Admin\NotificationAdminApiController::class, 'retry'])->name('retry');
        });
    });
});
