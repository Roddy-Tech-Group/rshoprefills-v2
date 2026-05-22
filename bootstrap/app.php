<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\ResolveRegion;
use App\Http\Middleware\TraceRequestMiddleware;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;

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
    // Console commands live under domain folders, which are NOT auto-discovered
    // (only app/Console/Commands is). Register the domain command dirs so the
    // scheduler (reconcile:*, zendit:*, etc.) can resolve them.
    ->withCommands([
        __DIR__.'/../app/Domain/Reconciliation/Console',
        __DIR__.'/../app/Domain/Fulfillment/Console',
        __DIR__.'/../app/Domain/Payment/Console',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => AdminAuth::class,
        ]);

        $middleware->append(TraceRequestMiddleware::class);

        // Locks the storefront catalog to the customer's chosen country/region.
        $middleware->web(append: [
            ResolveRegion::class,
            EnsureAccountActive::class,
        ]);

        // Enable stateful sessions for API routes so AJAX requests can resolve the authenticated user
        $middleware->api(append: [
            EncryptCookies::class,
            StartSession::class,
        ]);

        $middleware->redirectGuestsTo(fn () => request()->is('admin*') ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn () => request()->is('admin*') ? route('admin.dashboard') : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
