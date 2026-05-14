<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin list views — read-only Blade pages backed by shipped models.
// FYI for backend: when admin role/middleware ships, wrap this group in `role:admin` (or similar) to lock customers out.
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('orders', 'admin.orders')->name('orders');
    Route::view('customers', 'admin.customers')->name('customers');
    Route::view('transactions', 'admin.transactions')->name('transactions');
    Route::view('wallets', 'admin.wallets')->name('wallets');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
