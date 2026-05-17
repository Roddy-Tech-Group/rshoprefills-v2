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
Route::post('webhooks/flutterwave', [\App\Http\Controllers\Api\Webhooks\FlutterwaveWebhookController::class, 'handle'])->name('api.webhooks.flutterwave');
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

// Protected Dashboard & Wallet APIs
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [UserDashboardController::class, 'index'])->name('api.dashboard');

    Route::prefix('wallets')->name('api.wallets.')->group(function () {
        Route::get('/', [UserWalletController::class, 'index'])->name('index');
        Route::get('{currency}', [UserWalletController::class, 'show'])->name('show');
        Route::post('fund/initiate', [UserWalletController::class, 'initiateFunding'])->name('fund.initiate');
    });

    Route::prefix('checkout')->name('api.checkout.')->group(function () {
        Route::post('place-order', [\App\Http\Controllers\Api\CheckoutApiController::class, 'placeOrder'])->name('place-order');
    });

    Route::prefix('orders')->name('api.orders.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CheckoutApiController::class, 'index'])->name('index');
        Route::get('{orderNumber}', [\App\Http\Controllers\Api\CheckoutApiController::class, 'show'])->name('show');
    });

    Route::get('transactions', [UserTransactionController::class, 'index'])->name('api.transactions.index');
});
