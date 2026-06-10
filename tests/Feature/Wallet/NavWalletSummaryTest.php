<?php

namespace Tests\Feature\Wallet;

use App\Domain\Shared\Enums\Currency;
use App\Models\CurrencyRate;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavWalletSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_funded_wallet_shows_zero_usd(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 0]);

        $summary = $user->navWalletSummary();

        $this->assertSame(Currency::USD, $summary['currency']);
        $this->assertSame(0.0, $summary['amount']);
        $this->assertFalse($summary['combined']);
    }

    public function test_single_funded_wallet_shows_its_own_currency(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 0]);
        Wallet::factory()->for($user)->create(['currency' => 'XAF', 'balance' => 6000]);

        $summary = $user->navWalletSummary();

        $this->assertSame(Currency::XAF, $summary['currency']);
        $this->assertSame(6000.0, $summary['amount']);
        $this->assertFalse($summary['combined']);
    }

    public function test_multiple_funded_wallets_combine_into_usd(): void
    {
        CurrencyRate::create(['code' => 'USD', 'name' => 'US Dollar', 'type' => 'fiat', 'rate_per_usd' => 1, 'is_active' => true]);
        CurrencyRate::create(['code' => 'XAF', 'name' => 'CFA Franc', 'type' => 'fiat', 'rate_per_usd' => 600, 'is_active' => true]);

        $user = User::factory()->create();
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 10]);
        // 6000 XAF / 600 = $10 -> combined $20.
        Wallet::factory()->for($user)->create(['currency' => 'XAF', 'balance' => 6000]);

        $summary = $user->navWalletSummary();

        $this->assertSame(Currency::USD, $summary['currency']);
        $this->assertSame(20.0, $summary['amount']);
        $this->assertTrue($summary['combined']);
    }

    public function test_unknown_rate_falls_back_to_one_to_one(): void
    {
        // Only USD has a rate row; the second currency is missing -> treated as 1:1.
        CurrencyRate::create(['code' => 'USD', 'name' => 'US Dollar', 'type' => 'fiat', 'rate_per_usd' => 1, 'is_active' => true]);

        $user = User::factory()->create();
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 5]);
        Wallet::factory()->for($user)->create(['currency' => 'GHS', 'balance' => 5]);

        $summary = $user->navWalletSummary();

        $this->assertSame(10.0, $summary['amount']);
        $this->assertTrue($summary['combined']);
    }
}
