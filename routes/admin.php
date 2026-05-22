<?php

use App\Http\Controllers\Admin\AdminCatalogController;
use App\Http\Controllers\Admin\AdminCommerceController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFintechController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\AdminSreController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\NotificationAdminApiController;
use App\Http\Controllers\ThemeController;
use App\Models\Order;
use App\Models\User;
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

        // Persists the admin's own light/dark/system preference, kept separate
        // from the customer side (different table + guard).
        Route::post('theme', [ThemeController::class, 'updateAdmin'])->name('theme');

        // Admin content views — read-only Blade pages backed by shipped models.
        // Replace with controllers when CRUD/actions ship.
        Route::view('products', 'admin.products')->name('products');
        Route::view('orders', 'admin.orders')->name('orders');
        Route::get('orders/{order}', function (Order $order) {
            return view('admin.order', [
                'order' => $order->load(['user', 'items', 'paymentAttempts']),
            ]);
        })->name('order');
        Route::view('customers', 'admin.customers')->name('customers');
        Route::get('customers/{user}', function (User $user) {
            $user->load([
                'wallets',
                'orders' => fn ($query) => $query->latest()->limit(10),
                'walletTransactions' => fn ($query) => $query->latest()->limit(10),
            ]);

            return view('admin.customer', [
                'user' => $user,
                'ordersCount' => $user->orders()->count(),
                'totalSpent' => (float) $user->orders()
                    ->whereIn('order_status', ['completed', 'partially_completed'])
                    ->sum('total_amount'),
                'unreadNotifications' => $user->notifications()->whereNull('read_at')->count(),
                'kyc' => $user->kycSubmissions()->first(),
            ]);
        })->name('customer');

        // KYC review — serve documents from the private disk + approve / reject.
        Route::get('kyc/{submission}/document/{type}', [AdminKycController::class, 'document'])->name('kyc.document');
        Route::post('kyc/{submission}/approve', [AdminKycController::class, 'approve'])->name('kyc.approve');
        Route::post('kyc/{submission}/reject', [AdminKycController::class, 'reject'])->name('kyc.reject');

        // Customer admin actions: edit profile, ban/unban, hold/release funds.
        Route::patch('customers/{user}', [AdminCustomerController::class, 'update'])->name('customer.update');
        Route::post('customers/{user}/ban', [AdminCustomerController::class, 'toggleBan'])->name('customer.ban');
        Route::post('customers/{user}/funds', [AdminCustomerController::class, 'toggleFunds'])->name('customer.funds');
        Route::view('transactions', 'admin.transactions')->name('transactions');
        Route::view('wallets', 'admin.wallets')->name('wallets');
        Volt::route('rates', 'admin.rates')->name('rates');
        Volt::route('account', 'admin.account')->name('account');
        Volt::route('notifications', 'admin.notifications')->name('notifications');

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

        // Admin SRE & Enterprise Operations API
        Route::prefix('api/sre')->name('api.sre.')->group(function () {
            Route::get('audit-logs', [AdminSreController::class, 'auditLogs'])->name('audit-logs');
            Route::get('ledger-events', [AdminSreController::class, 'ledgerEvents'])->name('ledger-events');
            Route::get('reconciliation-reports', [AdminSreController::class, 'reconciliationReports'])->name('reconciliation-reports');
            Route::get('system-metrics', [AdminSreController::class, 'systemMetrics'])->name('system-metrics');
        });

        // Admin Commerce Monitoring & Actions API
        Route::prefix('api/commerce')->name('api.commerce.')->group(function () {
            Route::get('orders', [AdminCommerceController::class, 'listOrders'])->name('orders');
            Route::get('payments', [AdminCommerceController::class, 'listPayments'])->name('payments');
            Route::get('fulfillments', [AdminCommerceController::class, 'listFulfillmentLogs'])->name('fulfillments');
            Route::post('orders/{itemId}/retry-fulfillment', [AdminCommerceController::class, 'retryFulfillment'])->name('retry-fulfillment');
            Route::post('orders/{orderId}/refund', [AdminCommerceController::class, 'refundOrder'])->name('refund');
        });

        // Admin Catalog API
        Route::prefix('api/catalog')->name('api.catalog.')->group(function () {
            Route::post('sync/zendit', [AdminCatalogController::class, 'syncZendit'])->name('sync.zendit');
            Route::post('sync/zendit-esims', [AdminCatalogController::class, 'syncZenditEsims'])->name('sync.zendit-esims');
            Route::post('sync/zendit-topups', [AdminCatalogController::class, 'syncZenditTopups'])->name('sync.zendit-topups');
            Route::get('products', [AdminCatalogController::class, 'products'])->name('products');
            Route::patch('products/{product}/toggle-active', [AdminCatalogController::class, 'toggleActive']);
            Route::patch('products/{product}/toggle-featured', [AdminCatalogController::class, 'toggleFeatured']);
            Route::patch('products/{product}/toggle-popular', [AdminCatalogController::class, 'togglePopular']);
        });

        // Admin Notifications & Compliance API
        Route::prefix('api/notifications')->name('api.notifications.')->group(function () {
            Route::get('/', [NotificationAdminApiController::class, 'index'])->name('index');
            Route::get('deliveries', [NotificationAdminApiController::class, 'deliveries'])->name('deliveries');
            Route::get('metrics', [NotificationAdminApiController::class, 'metrics'])->name('metrics');
            Route::post('{id}/retry', [NotificationAdminApiController::class, 'retry'])->name('retry');
        });
    });
});
