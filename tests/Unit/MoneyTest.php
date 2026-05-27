<?php

namespace Tests\Unit;

use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Services\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_formats_known_currencies_with_their_symbol(): void
    {
        $this->assertSame('₦25,000.00', Money::format(25000, 'NGN'));
        $this->assertSame('$25.00', Money::format(25, 'USD'));
        $this->assertSame('£12.50', Money::format(12.5, 'GBP'));
        $this->assertSame('₵100.00', Money::format(100, 'GHS'));
        $this->assertSame('FCFA3,000.00', Money::format(3000, 'XAF'));
    }

    public function test_it_handles_lowercase_currency_codes(): void
    {
        $this->assertSame('₦100.00', Money::format(100, 'ngn'));
    }

    public function test_it_falls_back_for_unknown_currency_codes(): void
    {
        $this->assertSame('XYZ 50.00', Money::format(50, 'XYZ'));
    }

    public function test_it_renders_amount_only_when_currency_is_null(): void
    {
        $this->assertSame('50.00', Money::format(50, null));
    }

    public function test_code_amount_uses_iso_prefix(): void
    {
        $this->assertSame('NGN 25,000.00', Money::codeAmount(25000, 'NGN'));
        $this->assertSame('USD 25.00', Money::codeAmount(25, 'USD'));
    }

    public function test_symbol_lookup(): void
    {
        $this->assertSame('₦', Money::symbol('NGN'));
        $this->assertSame('$', Money::symbol('USD'));
        $this->assertSame('XYZ', Money::symbol('XYZ'));
    }

    public function test_it_accepts_a_currency_enum_directly(): void
    {
        // Some callers pass the enum (e.g. wallet models cast `currency` to
        // Currency::class). The helper must not blow up when that happens.
        $this->assertSame('₦25,000.00', Money::format(25000, Currency::NGN));
        $this->assertSame('NGN 25,000.00', Money::codeAmount(25000, Currency::NGN));
        $this->assertSame('₦', Money::symbol(Currency::NGN));
    }
}
