<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Domain\Admin\Services\AdminAuthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLoginController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $adminAuthService,
    ) {}

    /**
     * Handle an incoming admin authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->adminAuthService->authenticate(
            $request->email,
            $request->password,
            $request->boolean('remember')
        );

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated admin session.
     */
    public function destroy(): RedirectResponse
    {
        $this->adminAuthService->logout();

        return redirect('/');
    }
}
