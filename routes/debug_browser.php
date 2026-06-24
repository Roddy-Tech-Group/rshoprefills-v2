<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

Route::get('/debug-login-browser', function () {
    $user = User::first();
    if (!$user) {
        $user = User::factory()->create();
    }
    Auth::login($user);
    return redirect()->route('dashboard.gift-cards.history');
});
