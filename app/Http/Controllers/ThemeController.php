<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Persists each account's light/dark/system preference.
 *
 * Customers and admins live on separate tables and guards, so each area has
 * its own endpoint that writes to the account resolved from its guard. The
 * front-end (partials/theme-engine) posts here whenever the toggle changes.
 */
class ThemeController extends Controller
{
    /**
     * The allowed theme choices.
     *
     * @var list<string>
     */
    private const CHOICES = ['light', 'dark', 'system'];

    /**
     * Persist the authenticated customer's theme preference.
     */
    public function update(Request $request): JsonResponse
    {
        return $this->persist($request, $request->user());
    }

    /**
     * Persist the authenticated admin's theme preference.
     */
    public function updateAdmin(Request $request): JsonResponse
    {
        return $this->persist($request, $request->user('admin'));
    }

    /**
     * Validate the choice and store it on the given account.
     */
    private function persist(Request $request, mixed $account): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(self::CHOICES)],
        ]);

        $account->forceFill(['theme' => $validated['theme']])->save();

        return response()->json(['theme' => $validated['theme']]);
    }
}
