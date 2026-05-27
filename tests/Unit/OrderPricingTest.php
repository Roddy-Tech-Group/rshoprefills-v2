<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

/**
 * Unit-level coverage for Order::usdTotal / usdSubtotal / usdMarkup /
 * hasSuspectPricing. These accessors are pure-PHP — no DB required — so we
 * skip the Laravel app boot and exercise them directly via the model.
 */
class OrderPricingTest extends TestCase
{
    /** @param array<string, mixed> $attributes */
    private function order(array $attributes): Order
    {
        $order = new Order;
        $order->forceFill($attributes);

        return $order;
    }

    public function test_usd_total_prefers_snapshot_metadata_when_present(): void
    {
        $order = $this->order([
            'display_currency' => 'NGN',
            'total_amount' => 2800.00,
            'subtotal_amount' => 2600.00,
            'metadata' => [
                'exchange_rate' => 1400.0,
                'settlement_total_usd' => 2.00,
                'settlement_subtotal_usd' => 1.85,
            ],
        ]);

        $this->assertSame(2.00, $order->usdTotal());
        $this->assertSame(1.85, $order->usdSubtotal());
        $this->assertSame(0.15, $order->usdMarkup());
    }

    public function test_usd_total_derives_from_rate_when_snapshot_is_missing(): void
    {
        $order = $this->order([
            'display_currency' => 'NGN',
            'total_amount' => 2800.00,
            'subtotal_amount' => 2590.00,
            'metadata' => ['exchange_rate' => 1400.0],
        ]);

        $this->assertSame(2.0, $order->usdTotal());
        $this->assertSame(1.85, $order->usdSubtotal());
    }

    public function test_usd_total_falls_back_to_display_amount_when_no_rate(): void
    {
        // Legacy pre-snapshot order: no exchange_rate in metadata. We can't
        // derive USD honestly, so return the display amount and let the
        // suspect-pricing flag warn admins.
        $order = $this->order([
            'display_currency' => 'NGN',
            'total_amount' => 2.13,
            'subtotal_amount' => 1.97,
            'metadata' => [],
        ]);

        $this->assertSame(2.13, $order->usdTotal());
        $this->assertSame(1.97, $order->usdSubtotal());
    }

    public function test_suspect_pricing_when_display_currency_non_usd_and_no_rate(): void
    {
        $order = $this->order([
            'display_currency' => 'NGN',
            'total_amount' => 2.13,
            'metadata' => [],
        ]);

        $this->assertTrue($order->hasSuspectPricing());
    }

    public function test_not_suspect_when_display_currency_is_usd(): void
    {
        $order = $this->order([
            'display_currency' => 'USD',
            'total_amount' => 6.74,
            'metadata' => [],
        ]);

        $this->assertFalse($order->hasSuspectPricing());
    }

    public function test_not_suspect_when_rate_snapshot_exists(): void
    {
        $order = $this->order([
            'display_currency' => 'NGN',
            'total_amount' => 2800.00,
            'metadata' => ['exchange_rate' => 1400.0],
        ]);

        $this->assertFalse($order->hasSuspectPricing());
    }

    public function test_exchange_rate_returns_null_for_zero_or_invalid(): void
    {
        $this->assertNull(
            $this->order(['metadata' => ['exchange_rate' => 0]])->exchangeRate(),
        );
        $this->assertNull(
            $this->order(['metadata' => ['exchange_rate' => 'wat']])->exchangeRate(),
        );
        $this->assertNull(
            $this->order(['metadata' => []])->exchangeRate(),
        );
    }
}
