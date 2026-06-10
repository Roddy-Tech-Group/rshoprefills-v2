<?php

namespace Tests\Feature\Payment;

use App\Domain\Payment\Support\ProcessingFee;
use Tests\TestCase;

class ProcessingFeeTest extends TestCase
{
    public function test_it_matches_the_flutterwave_receipt_for_international_mobile_money(): void
    {
        // The real receipt: XAF 3,000 international Senegal mobile money.
        $fee = ProcessingFee::for(3000, 'mobile_money', international: true);

        $this->assertSame(60.0, $fee['transaction_fee']);
        $this->assertSame(60.0, $fee['international_fee']);
        $this->assertSame(120.0, $fee['processing_fee']);
        $this->assertSame(9.0, $fee['vat']);            // 7.5% of 120
        $this->assertSame(3120.0, $fee['customer_total']); // what the customer pays
        $this->assertSame(2991.0, $fee['settlement']);     // amount - VAT
    }

    public function test_domestic_mobile_money_has_no_international_fee(): void
    {
        $fee = ProcessingFee::for(3000, 'mobile_money', international: false);

        $this->assertSame(60.0, $fee['transaction_fee']);
        $this->assertSame(0.0, $fee['international_fee']);
        $this->assertSame(60.0, $fee['processing_fee']);
        $this->assertSame(3060.0, $fee['customer_total']);
    }

    public function test_card_uses_its_own_rate(): void
    {
        $fee = ProcessingFee::for(1000, 'card');

        $this->assertSame(48.0, $fee['processing_fee']); // 4.8%
        $this->assertSame(1048.0, $fee['customer_total']);
    }

    public function test_wallet_and_crypto_carry_no_fee(): void
    {
        foreach (['wallet', 'crypto'] as $method) {
            $fee = ProcessingFee::for(5000, $method, international: true);

            $this->assertSame(0.0, $fee['processing_fee'], "{$method} should be fee-free");
            $this->assertSame(5000.0, $fee['customer_total']);
        }
    }

    public function test_zero_or_negative_amount_is_safe(): void
    {
        $fee = ProcessingFee::for(0, 'card');

        $this->assertSame(0.0, $fee['processing_fee']);
        $this->assertSame(0.0, $fee['customer_total']);
    }
}
