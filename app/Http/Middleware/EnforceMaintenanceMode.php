<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard kill-switch for transactional writes when maintenance mode is on.
 * Browsing, auth and read-only routes stay open - only state-changing
 * endpoints (checkout, cart writes, wallet funding, withdrawals) refuse.
 *
 * Admin operators always bypass so they can still clean up data, run
 * reconciliations and toggle the switch back off.
 *
 * Apply via the `maintenance-guard` alias selectively to write routes;
 * do NOT attach to the global web group - that would block the storefront
 * itself and customers wouldn't even see the banner explaining the outage.
 */
class EnforceMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isMaintenanceOn()) {
            return $next($request);
        }

        // Admin operators (super_admin / admin / moderator) bypass so they
        // can keep working through the outage.
        $user = $request->user();
        if ($user && method_exists($user, 'role') && in_array($user->role, ['super_admin', 'admin', 'moderator'], true)) {
            return $next($request);
        }
        if ($user && in_array((string) ($user->role ?? ''), ['super_admin', 'admin', 'moderator'], true)) {
            return $next($request);
        }

        $message = (string) SiteSetting::get(
            'system.maintenance_message',
            'We are running quick maintenance. Back shortly.',
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'maintenance' => true,
            ], 503);
        }

        return back()->withErrors(['maintenance' => $message]);
    }

    private function isMaintenanceOn(): bool
    {
        return in_array(
            strtolower((string) SiteSetting::get('system.maintenance_mode', 'off')),
            ['on', 'true', '1'],
            true,
        );
    }
}
