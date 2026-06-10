<?php

namespace Tests\Feature\Wallet;

use App\Models\CurrencyRate;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavWalletSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function usdAndXafRates(): void
    {
        CurrencyRate::create(['code' => 'USD', 'name' => 'US Dollar', 'type' => 'fiat', 'rate_per_usd' => 1, 'is_active' => true]);
        CurrencyRate::create(['code' => 'XAF', 'name' => 'CFA Franc', 'type' => 'fiat', 'rate_per_usd' => 600, 'is_active' => true]);
    }

    public function test_no_funded_wallet_is_zero_usd(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 0]);

        $summary = $user->navWalletSummary();

        $this->assertSame(0.0, $summary['amount']);
        $this->assertFalse($summary['combined']);
    }

    public function test_single_non_usd_wallet_is_converted_to_usd(): void
    {
        $this->usdAndXafRates();

        $user = User::factory()->create();
        // 6000 XAF / 600 = $10.
        Wallet::factory()->for($user)->create(['currency' => 'XAF', 'balance' => 6000]);

        $summary = $user->navWalletSummary();

        $this->assertSame(10.0, $summary['amount']);
        $this->assertFalse($summary['combined']);
    }

    public function test_multiple_funded_wallets_combine_into_usd(): void
    {
        $this->usdAndXafRates();

        $user = User::factory()->create();
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 10]);
        // 6000 XAF / 600 = $10 -> combined $20.
        Wallet::factory()->for($user)->create(['currency' => 'XAF', 'balance' => 6000]);

        $summary = $user->navWalletSummary();

        $this->assertSame(20.0, $summary['amount']);
        $this->assertTrue($summary['combined']);
    }

    public function test_unknown_rate_falls_back_to_one_to_one(): void
    {
        CurrencyRate::create(['code' => 'USD', 'name' => 'US Dollar', 'type' => 'fiat', 'rate_per_usd' => 1, 'is_active' => true]);

        $user = User::factory()->create();
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 5]);
        Wallet::factory()->for($user)->create(['currency' => 'GHS', 'balance' => 5]);

        $summary = $user->navWalletSummary();

        $this->assertSame(10.0, $summary['amount']);
        $this->assertTrue($summary['combined']);
    }

    /**
     * @return array<string, array{0: float, 1: string}>
     */
    public static function compactCases(): array
    {
        return [
            'zero' => [0.0, '$0.00'],
            'cents' => [10.5, '$10.50'],
            'just under 1k' => [999.99, '$999.99'],
            'exactly 1k' => [1000.0, '$1k'],
            'one point one k' => [1100.0, '$1.1k'],
            'truncates not rounds' => [1290.0, '$1.2k'],
            'never overstates' => [1990.0, '$1.9k'],
            'tens of k' => [12345.0, '$12.3k'],
            'exactly 1m' => [1_000_000.0, '$1M'],
            'millions' => [2_500_000.0, '$2.5M'],
        ];
    }

    #[DataProvider('compactCases')]
    public function test_compact_usd_formatting(float $amount, string $expected): void
    {
        $this->assertSame($expected, Wallet::compactUsd($amount));
    }
}
