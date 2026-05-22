<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin actions on a customer account: edit profile, ban/unban, hold/release
 * wallet funds. Lives behind the `admin` middleware group.
 */
class AdminCustomerController extends Controller
{
    /**
     * Edit a customer's core profile fields.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:male,female,other'],
        ]);

        $user->update($validated);

        return back()->with('status', 'Customer details updated.');
    }

    /**
     * Ban or unban the customer. Banning blocks login + access via middleware.
     */
    public function toggleBan(User $user): RedirectResponse
    {
        $banned = $user->banned_at !== null;

        $user->update(['banned_at' => $banned ? null : now()]);

        return back()->with('status', $banned
            ? 'Customer unbanned.'
            : 'Customer banned. They have been blocked from signing in.');
    }

    /**
     * Hold or release the customer's funds by freezing every wallet. Frozen
     * wallets cannot be debited (guarded in WalletService::debit).
     */
    public function toggleFunds(User $user): RedirectResponse
    {
        // If any wallet is still active we are holding; otherwise releasing.
        $hold = $user->wallets()->where('is_active', true)->exists();

        $user->wallets()->update(['is_active' => ! $hold]);

        return back()->with('status', $hold
            ? 'Customer funds placed on hold.'
            : 'Customer funds released.');
    }
}
