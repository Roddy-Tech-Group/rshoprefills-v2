<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\ResolveRegion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/admin.php',
        ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => AdminAuth::class,
        ]);

        // Locks the storefront catalog to the customer's chosen country/region.
        $middleware->web(append: [
            ResolveRegion::class,
        ]);

        // Enable stateful sessions for API routes so AJAX requests can resolve the authenticated user
        $middleware->api(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->redirectGuestsTo(fn () => request()->is('admin*') ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn () => request()->is('admin*') ? route('admin.dashboard') : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
