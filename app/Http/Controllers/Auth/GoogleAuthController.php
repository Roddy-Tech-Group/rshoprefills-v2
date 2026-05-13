<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\GoogleAuthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Handles Google OAuth redirect and callback.
 *
 * The actual business logic lives in GoogleAuthService — this controller
 * is a thin HTTP adapter that translates web requests into service calls.
 */
class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google after authentication.
     *
     * Delegates to GoogleAuthService for the find-or-create logic,
     * then logs the user in and redirects to dashboard.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::warning('Google OAuth callback failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('status', __('Google authentication failed. Please try again.'));
        }

        try {
            $result = $this->googleAuthService->findOrCreateUser($googleUser);
            $user = $result['user'];

            Auth::login($user, remember: true);
            session()->regenerate();

            return redirect()->intended(route('dashboard'));
        } catch (\RuntimeException $e) {
            Log::error('Google OAuth user creation failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('status', $e->getMessage());
        }
    }
}
