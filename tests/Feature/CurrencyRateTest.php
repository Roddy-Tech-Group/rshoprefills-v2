<?php

namespace Tests\Feature;

use App\Models\CurrencyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyRateTest extends TestCase
{
    use RefreshDatabase;

    private function seedRates(): void
    {
        CurrencyRate::create(['code' => 'USD', 'name' => 'United States Dollar', 'type' => 'fiat', 'rate_per_usd' => 1.04, 'is_active' => true]);
        CurrencyRate::create(['code' => 'XAF', 'name' => 'Central African CFA Franc', 'type' => 'fiat', 'rate_per_usd' => 788, 'is_active' => true]);
        CurrencyRate::create(['code' => 'NGN', 'name' => 'Nigerian Naira', 'type' => 'fiat', 'rate_per_usd' => 1400, 'is_active' => false]);
    }

    public function test_convert_multiplies_a_usd_amount_by_the_rate(): void
    {
        $rate = new CurrencyRate(['rate_per_usd' => 788]);

        $this->assertEqualsWithDelta(15760.0, $rate->convert(20.0), 0.001);
    }

    public function test_resolve_returns_the_active_row_for_a_code(): void
    {
        $this->seedRates();

        $this->assertSame('XAF', CurrencyRate::resolve('xaf')->code);
    }

    public function test_resolve_falls_back_to_usd_for_an_unknown_currency(): void
    {
        $this->seedRates();

        $this->assertSame('USD', CurrencyRate::resolve('ZZZ')->code);
    }

    public function test_resolve_falls_back_to_usd_for_an_inactive_currency(): void
    {
        $this->seedRates();

        // NGN exists but is inactive — it must not be selected.
        $this->assertSame('USD', CurrencyRate::resolve('NGN')->code);
    }

    public function test_resolve_returns_a_synthetic_usd_rate_when_the_table_is_empty(): void
    {
        $rate = CurrencyRate::resolve('XAF');

        $this->assertSame('USD', $rate->code);
        $this->assertEqualsWithDelta(1.0, (float) $rate->rate_per_usd, 0.001);
    }

    public function test_resolve_handles_null_and_blank_input(): void
    {
        $this->seedRates();

        $this->assertSame('USD', CurrencyRate::resolve(null)->code);
        $this->assertSame('USD', CurrencyRate::resolve('')->code);
    }
}
