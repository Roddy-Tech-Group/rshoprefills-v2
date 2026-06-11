<?php

namespace Tests\Feature\Wallet;

use App\Domain\Wallet\Exceptions\StaleRateException;
use App\Domain\Wallet\Services\CurrencyRateService;
use App\Models\CurrencyRate;
use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Read-only admin displays must keep showing a number even when the live
 * exchange_rates registry has gone stale - the staleness throw is a guard for
 * transactional paths only. A lagging rates cron used to zero the admin
 * customer page's wallet headline through exactly this.
 */
class CurrencyRateDisplayResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function staleXafRate(): void
    {
        ExchangeRate::create([
            'base_currency' => 'XAF',
            'target_currency' => 'USD',
            'rate' => 1 / 600,
            'provider' => 'system_sync',
            'source' => 'currency_rates_db',
            'is_active' => true,
            'fetched_at' => now()->subDays(3),
            'expires_at' => now()->subDays(2),
        ]);
    }

    public function test_transactional_convert_still_throws_on_critically_stale_rates(): void
    {
        $this->staleXafRate();

        $this->expectException(StaleRateException::class);

        app(CurrencyRateService::class)->convert(6000, 'XAF', 'USD');
    }

    public function test_display_conversion_uses_the_stale_rate_instead_of_throwing(): void
    {
        $this->staleXafRate();

        $usd = app(CurrencyRateService::class)->convertForDisplay(6000, 'XAF', 'USD');

        $this->assertEqualsWithDelta(10.0, $usd, 0.01);
    }

    public function test_display_conversion_falls_back_to_currency_rates_when_registry_is_empty(): void
    {
        CurrencyRate::create(['code' => 'USD', 'name' => 'US Dollar', 'type' => 'fiat', 'rate_per_usd' => 1, 'is_active' => true]);
        CurrencyRate::create(['code' => 'XAF', 'name' => 'CFA Franc', 'type' => 'fiat', 'rate_per_usd' => 600, 'is_active' => true]);

        $usd = app(CurrencyRateService::class)->convertForDisplay(6000, 'XAF', 'USD');

        $this->assertEqualsWithDelta(10.0, $usd, 0.01);
    }
}
