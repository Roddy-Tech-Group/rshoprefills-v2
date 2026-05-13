<?php

namespace App\Domain\Admin\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Handles admin authentication business logic.
 *
 * Keeps the controller thin — all credential validation,
 * rate limiting, session management, and login recording
 * logic lives here.
 */
class AdminAuthService
{
    /**
     * Maximum failed login attempts before throttling kicks in.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Throttle decay time in seconds (2 minutes).
     */
    private const DECAY_SECONDS = 120;

    /**
     * Attempt to authenticate an admin.
     *
     * @throws ValidationException
     */
    public function authenticate(string $email, string $password, bool $remember = false): Admin
    {
        $throttleKey = $this->throttleKey($email);

        $this->ensureIsNotRateLimited($throttleKey);

        if (! Auth::guard('admin')->attempt(
            ['email' => $email, 'password' => $password],
            $remember,
        )) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            Log::warning('Admin login failed', ['email' => $email]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Verify the admin account is active
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (! $admin->isActive()) {
            Auth::guard('admin')->logout();

            Log::warning('Deactivated admin attempted login', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
            ]);

            throw ValidationException::withMessages([
                'email' => __('Your admin account has been deactivated. Contact a super administrator.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Regenerate session to prevent fixation attacks
        session()->regenerate();

        // Track login timestamp
        $admin->recordLogin();

        Log::info('Admin logged in', [
            'admin_id' => $admin->id,
            'email' => $admin->email,
        ]);

        return $admin;
    }

    /**
     * Log the admin out and invalidate the session.
     */
    public function logout(): void
    {
        $admin = Auth::guard('admin')->user();

        Auth::guard('admin')->logout();

        // Invalidate the session and regenerate the CSRF token
        session()->invalidate();
        session()->regenerateToken();

        if ($admin) {
            Log::info('Admin logged out', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(string $key): void
    {
        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        Log::warning('Admin login rate limited', ['key' => $key]);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Generate a throttle key scoped to the email and IP.
     */
    private function throttleKey(string $email): string
    {
        return 'admin-login:'.Str::transliterate(
            Str::lower($email).'|'.request()->ip()
        );
    }
}
