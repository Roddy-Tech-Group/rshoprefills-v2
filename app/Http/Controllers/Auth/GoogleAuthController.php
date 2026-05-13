<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return $this->driver()->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = $this->driver()->user();

        $user = User::firstOrNew(['email' => $googleUser->getEmail()]);

        if (! $user->exists) {
            $user->name = $googleUser->getName();
            $user->email_verified_at = now();
        }

        $user->google_id = $googleUser->getId();
        $user->avatar = $googleUser->getAvatar();
        $user->save();

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Build the Google driver. In local dev we skip the OAuth state check via
     * stateless() (sessions don't share between localhost and 127.0.0.1) and
     * disable SSL verification on the Guzzle client (Windows PHP installs
     * often lack a CA bundle, causing cURL error 60). Production keeps both on.
     */
    protected function driver()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        if (app()->environment('local')) {
            $driver = $driver
                ->stateless()
                ->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        }

        return $driver;
    }
}
