<?php

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
Route::post('webhooks/flutterwave', [FlutterwaveWebhookController::class, 'handle'])->name('webhooks.flutterwave');

// Storefront Catalog APIs (Public)
Route::prefix('storefront')->name('api.storefront.')->group(function () {
    Route::get('categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('subcategories', [CatalogController::class, 'subcategories'])->name('subcategories');
    Route::get('products', [CatalogController::class, 'products'])->name('products');
    Route::get('products/{slug}', [CatalogController::class, 'product'])->name('product.show');

    // eSIM Specific Flow
    Route::get('esims/countries', [EsimCatalogController::class, 'countries'])->name('esims.countries');
    Route::get('esims/{slug}', [EsimCatalogController::class, 'show'])->name('esims.show');
});

// Protected Dashboard & Wallet APIs
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [UserDashboardController::class, 'index'])->name('api.dashboard');

    Route::prefix('wallets')->name('api.wallets.')->group(function () {
        Route::get('/', [UserWalletController::class, 'index'])->name('index');
        Route::get('{currency}', [UserWalletController::class, 'show'])->name('show');
        Route::post('fund/initiate', [UserWalletController::class, 'initiateFunding'])->name('fund.initiate');
    });

    Route::get('transactions', [UserTransactionController::class, 'index'])->name('api.transactions.index');
});
