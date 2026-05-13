<?php

namespace App\Listeners;

use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

/**
 * Creates a default wallet when a new user registers.
 *
 * This listener fires on the Registered event, which is dispatched by:
 * - The Livewire register component (credential sign-up)
 * - The GoogleAuthService (Google OAuth sign-up)
 *
 * Each user gets exactly one wallet with a default currency of USD
 * and a zero balance. The unique constraint on wallets.user_id
 * prevents duplicate wallets at the database level.
 */
class CreateWalletForNewUser
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Guard: skip if wallet already exists (idempotency)
        if ($user->wallet()->exists()) {
            return;
        }

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        Log::info('Wallet created for new user', [
            'user_id' => $user->id,
        ]);
    }
}
