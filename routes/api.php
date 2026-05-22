<?php

use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\CatalogController;
use App\Http\Controllers\Api\Storefront\EsimCatalogController;
use App\Http\Controllers\Api\UserDashboardController;
use App\Http\Controllers\Api\UserTransactionController;
use App\Http\Controllers\Api\UserWalletController;
use App\Http\Controllers\Webhooks\FlutterwaveWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Webhooks (No auth required)
Route::post('webhooks/flutterwave', [FlutterwaveWebhookController::class, 'handle'])->name('api.webhooks.flutterwave');
Route::post('webhooks/nowpayments', [\App\Http\Controllers\Api\Webhooks\NowPaymentsWebhookController::class, 'handle'])->name('api.webhooks.nowpayments');

// Storefront Catalog APIs (Public)
Route::prefix('storefront')->name('api.storefront.')->group(function () {
    Route::get('categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('subcategories', [CatalogController::class, 'subcategories'])->name('subcategories');
    Route::get('products', [CatalogController::class, 'products'])->name('products');
    Route::get('products/{slug}', [CatalogController::class, 'product'])->name('product.show');

    // eSIM Specific Flow
    Route::get('esims/countries', [EsimCatalogController::class, 'countries'])->name('esims.countries');
    Route::get('esims/{slug}', [EsimCatalogController::class, 'show'])->name('esims.show');

    // Cart Flow
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::post('/items', [CartController::class, 'addItem'])->name('items.add');
        Route::patch('/items/{id}', [CartController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{id}', [CartController::class, 'removeItem'])->name('items.remove');
        Route::delete('/', [CartController::class, 'clear'])->name('clear');
        Route::post('/merge', [CartController::class, 'merge'])->name('merge');
    });
});

// Newsletter Subscriptions (Public)
Route::post('newsletter/subscribe', [\App\Http\Controllers\Api\NewsletterApiController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('newsletter/unsubscribe', [\App\Http\Controllers\Api\NewsletterApiController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Protected Dashboard & Wallet APIs
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [UserDashboardController::class, 'index'])->name('api.dashboard');

    Route::prefix('wallets')->name('api.wallets.')->group(function () {
        Route::get('/', [UserWalletController::class, 'index'])->name('index');
        Route::get('/fundings', [UserWalletController::class, 'fundings'])->name('fundings');
        Route::get('/fundings/{reference}', [UserWalletController::class, 'fundingDetails'])->name('fundings.show');
        Route::get('{currency}', [UserWalletController::class, 'show'])->name('show');
        Route::post('fund/initiate', [UserWalletController::class, 'initiateFunding'])->name('fund.initiate');

        // Transaction PIN Routes
        Route::prefix('pin')->name('pin.')->group(function () {
            Route::get('status', [\App\Http\Controllers\Api\Wallet\TransactionPinController::class, 'status'])->name('status');
            Route::post('setup', [\App\Http\Controllers\Api\Wallet\TransactionPinController::class, 'setup'])->name('setup');
            Route::post('verify', [\App\Http\Controllers\Api\Wallet\TransactionPinController::class, 'verify'])->name('verify');
            Route::put('change', [\App\Http\Controllers\Api\Wallet\TransactionPinController::class, 'change'])->name('change');
            Route::post('reset/request', [\App\Http\Controllers\Api\Wallet\TransactionPinController::class, 'requestReset'])->name('reset.request');
            Route::post('reset/confirm', [\App\Http\Controllers\Api\Wallet\TransactionPinController::class, 'confirmReset'])->name('reset.confirm');
            Route::delete('remove', [\App\Http\Controllers\Api\Wallet\TransactionPinController::class, 'remove'])->name('remove');
        });
    });

    Route::prefix('checkout')->name('api.checkout.')->group(function () {
        Route::post('place-order', [\App\Http\Controllers\Api\CheckoutApiController::class, 'placeOrder'])->name('place-order');
    });

    Route::prefix('orders')->name('api.orders.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CheckoutApiController::class, 'index'])->name('index');
        Route::get('{orderNumber}', [\App\Http\Controllers\Api\CheckoutApiController::class, 'show'])->name('show');
    });

    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationApiController::class, 'index'])->name('index');
        Route::get('unread-count', [\App\Http\Controllers\Api\NotificationApiController::class, 'unreadCount'])->name('unread-count');
        Route::patch('{id}/read', [\App\Http\Controllers\Api\NotificationApiController::class, 'markAsRead'])->name('read');
        Route::patch('read-all', [\App\Http\Controllers\Api\NotificationApiController::class, 'markAllAsRead'])->name('read-all');
    });

    Route::prefix('notification-preferences')->name('api.notification-preferences.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationApiController::class, 'getPreferences'])->name('get');
        Route::put('/', [\App\Http\Controllers\Api\NotificationApiController::class, 'updatePreferences'])->name('update');
    });

    Route::prefix('payment-sessions')->name('api.payment-sessions.')->group(function () {
        Route::get('{id}', [\App\Http\Controllers\Api\PaymentSessionController::class, 'show'])->name('show');
        Route::get('{id}/status', [\App\Http\Controllers\Api\PaymentSessionController::class, 'status'])->name('status');
        Route::post('{id}/cancel', [\App\Http\Controllers\Api\PaymentSessionController::class, 'cancel'])->name('cancel');
        Route::post('{id}/verify', [\App\Http\Controllers\Api\PaymentSessionController::class, 'verify'])->name('verify');
        Route::post('{id}/pay', [\App\Http\Controllers\Api\PaymentSessionController::class, 'pay'])->name('pay');
    });

    Route::get('transactions', [UserTransactionController::class, 'index'])->name('api.transactions.index');
});
