<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\GoogleAuthService;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

/**
 * Handles Google OAuth redirect and callback.
 *
 * The actual business logic lives in GoogleAuthService — this controller
 * is a thin HTTP adapter that translates web requests into service calls.
 *
 * The driver() helper (from Roddy's branch) handles local-dev quirks:
 * stateless mode for session mismatches and disabled SSL verification
 * for Windows PHP installs without a CA bundle.
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
        return $this->driver()->redirect();
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
            $googleUser = $this->driver()->user();
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

    /**
     * Build the Google driver. In local dev we skip the OAuth state check via
     * stateless() (sessions don't share between localhost and 127.0.0.1) and
     * disable SSL verification on the Guzzle client (Windows PHP installs
     * often lack a CA bundle, causing cURL error 60). Production keeps both on.
     */
    protected function driver()
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('google');

        if (app()->environment('local')) {
            $driver = $driver
                ->stateless()
                ->setHttpClient(new Client(['verify' => false]));
        }

        return $driver;
    }
}
