<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_explicit_preference_returns_that_currency(): void
    {
        $user = User::factory()->create(['display_currency' => 'NGN']);

        $this->assertSame('NGN', $user->displayCurrency());
    }

    public function test_user_without_preference_falls_back_to_first_wallet_currency(): void
    {
        $user = User::factory()->create(['display_currency' => null]);
        Wallet::factory()->create(['user_id' => $user->id, 'currency' => \App\Domain\Shared\Enums\Currency::GHS, 'balance' => 0]);

        $this->assertSame('GHS', $user->displayCurrency());
    }

    public function test_user_without_preference_or_wallets_defaults_to_usd(): void
    {
        $user = User::factory()->create(['display_currency' => null]);
        $user->wallets()->delete();

        $this->assertSame('USD', $user->displayCurrency());
    }

    public function test_lowercase_preference_is_normalised(): void
    {
        $user = User::factory()->create(['display_currency' => 'ngn']);

        $this->assertSame('NGN', $user->displayCurrency());
    }
}
