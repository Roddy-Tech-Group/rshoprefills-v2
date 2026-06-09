<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\CaptureReferralCookie;
use App\Http\Middleware\EnforceMaintenanceMode;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAccountNotSuspended;
use App\Http\Middleware\ResolveRegion;
use App\Http\Middleware\TraceRequestMiddleware;
use App\Http\Middleware\VerifyTurnstile;
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
            // Selectively applied to write routes (checkout, cart writes,
            // wallet funding). Suspended users stay logged in so they can
            // see the banner + request a review - only the action is refused.
            'not-suspended' => EnsureAccountNotSuspended::class,
            // Hard kill-switch for transactional writes when the admin flips
            // system.maintenance_mode on. Browsing stays open, only state
            // changes are refused. Admin operators always bypass.
            'maintenance-guard' => EnforceMaintenanceMode::class,
            'verify-turnstile' => VerifyTurnstile::class,
        ]);

        $middleware->append(TraceRequestMiddleware::class);

        // Locks the storefront catalog to the customer's chosen country/region.
        // CaptureReferralCookie persists `?ref=…` for 90 days so the referral
        // survives the visitor leaving and signing up later.
        $middleware->web(append: [
            ResolveRegion::class,
            EnsureAccountActive::class,
            CaptureReferralCookie::class,
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
        // Guard: duplicate failed_jobs UUID insert is a harmless race condition,
        // not a real application error. Downgrade to a warning so Sentry doesn't
        // fire alerts on it.
        $exceptions->report(function (\Illuminate\Database\UniqueConstraintViolationException $e) {
            if (str_contains($e->getMessage(), 'failed_jobs')) {
                \Illuminate\Support\Facades\Log::warning('Duplicate failed_jobs UUID — race condition (harmless)', [
                    'message' => $e->getMessage(),
                ]);

                return false; // Stop propagation — don't report to Sentry.
            }
        });

        $exceptions->dontFlash([
            'password',
            'password_confirmation',
            'transaction_pin',
            'api_key',
            'secret',
            'credentials',
        ]);
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
