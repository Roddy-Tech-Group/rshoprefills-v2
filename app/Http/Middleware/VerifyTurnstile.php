<?php

namespace App\Http\Middleware;

use App\Domain\Security\Services\TurnstileService;
use App\Support\TaggedCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyTurnstile
{
    public function handle(Request $request, Closure $next, string $context = 'auth')
    {
        // Enforce based on config
        $enforceAuth = config('services.turnstile.enforce_auth', true);
        $enforceCheckout = config('services.turnstile.enforce_checkout', true);
        $enforceContact = config('services.turnstile.enforce_contact', true);

        if ($context === 'auth' && ! $enforceAuth) {
            return $next($request);
        }

        if ($context === 'checkout' && ! $enforceCheckout) {
            return $request->attributes->set('turnstile_status', TurnstileService::STATUS_BYPASSED) ? $next($request) : $next($request);
        }

        if ($context === 'contact' && ! $enforceContact) {
            $request->attributes->set('turnstile_status', TurnstileService::STATUS_BYPASSED);

            return $next($request);
        }

        $service = TurnstileService::make();
        $token = $request->input('cf-turnstile-response');
        $ip = $request->ip();

        $result = $service->validateToken($token, $ip);

        $request->attributes->set('turnstile_status', $result['status']);

        if ($result['status'] === TurnstileService::STATUS_SUCCESS || $result['status'] === TurnstileService::STATUS_BYPASSED) {
            return $next($request);
        }

        if ($result['status'] === TurnstileService::STATUS_TIMEOUT) {
            if ($context === 'checkout' || $context === 'contact') {
                Log::warning("Turnstile timeout during {$context}. Failing OPEN temporarily.", ['ip' => $ip]);

                return $next($request);
            }
            // Auth fails CLOSED
            Log::warning('Turnstile timeout during auth. Failing CLOSED.', ['ip' => $ip]);

            return $this->reject($request, 'Security verification service is temporarily unavailable. Please try again later.');
        }

        // Invalid token
        $this->recordFailure($ip);
        Log::warning('Turnstile validation failed.', ['ip' => $ip, 'context' => $context]);

        return $this->reject($request, 'Security verification failed. Please refresh the page and try again.');
    }

    private function reject(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withInput()->withErrors(['cf-turnstile-response' => $message]);
    }

    private function recordFailure(string $ip): void
    {
        // Lightweight abuse cooldown logic
        $key = "turnstile_failures_{$ip}";
        $failures = TaggedCache::for(['security'])->get($key, 0);
        TaggedCache::for(['security'])->put($key, $failures + 1, now()->addMinutes(15));
    }
}
