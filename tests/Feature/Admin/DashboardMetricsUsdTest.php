<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Queries\DashboardMetricsQuery;
use App\Models\Category;
use App\Models\CurrencyRate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every figure the admin dashboard labels "USD" must be converted from the
 * order's display currency via its rate snapshot - never the raw stored
 * amount (a 4200 XAF sale is ~$7, not $4200).
 */
class DashboardMetricsUsdTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompletedOrder(User $user, string $currency, float $displayTotal, float $rate, float $usdTotal): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'RSR-TEST-'.fake()->unique()->numerify('######'),
            'cart_id' => null,
            'settlement_currency' => $currency,
            'display_currency' => $currency,
            'subtotal_amount' => $displayTotal,
            'markup_amount' => 0,
            'total_amount' => $displayTotal,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'order_status' => 'completed',
            'placed_at' => now(),
            'completed_at' => now(),
            'metadata' => ['exchange_rate' => $rate, 'settlement_total_usd' => $usdTotal, 'settlement_subtotal_usd' => $usdTotal],
        ]);
    }

    private function attachItem(Order $order, float $displaySubtotal, string $countryCode = 'US'): OrderItem
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'test',
            'quantity' => 1,
            'display_currency' => $order->display_currency,
            'display_amount' => $displaySubtotal,
            'provider_cost_usd' => 5.00,
            'markup_amount' => 0,
            'subtotal_amount' => $displaySubtotal,
            'product_snapshot' => ['country_code' => $countryCode, 'name' => 'Test product'],
            'fulfillment_status' => 'fulfilled',
        ]);
    }

    public function test_overview_revenue_sums_settlement_usd_across_currencies(): void
    {
        $user = User::factory()->create();
        $this->makeCompletedOrder($user, 'XAF', 4200.00, 600.0, 7.00);
        $this->makeCompletedOrder($user, 'USD', 14.00, 1.0, 14.00);

        $metrics = app(DashboardMetricsQuery::class)->getOverviewMetrics();

        $this->assertEqualsWithDelta(21.00, $metrics['total_revenue'], 0.01);
    }

    public function test_wallet_balance_total_converts_each_currency_honestly(): void
    {
        CurrencyRate::create(['code' => 'USD', 'name' => 'US Dollar', 'type' => 'fiat', 'rate_per_usd' => 1.04, 'is_active' => true]);
        CurrencyRate::create(['code' => 'XAF', 'name' => 'CFA Franc', 'type' => 'fiat', 'rate_per_usd' => 600, 'is_active' => true]);
        Setting::set('rcoin_usd_rate', 0.01);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        // $100 face value (never divided by the 1.04 spread row)
        Wallet::factory()->for($userA)->create(['currency' => 'USD', 'balance' => 100]);
        // 6000 XAF / 600 = $10
        Wallet::factory()->for($userB)->create(['currency' => 'XAF', 'balance' => 6000]);
        // 1000 Rcoin x $0.01 = $10
        Wallet::factory()->for($userB)->create(['currency' => 'RCOIN', 'balance' => 1000]);

        $metrics = app(DashboardMetricsQuery::class)->getOverviewMetrics();

        $this->assertEqualsWithDelta(120.00, $metrics['wallet_balance_total'], 0.01);
    }

    public function test_best_selling_countries_map_converts_display_amounts_to_usd(): void
    {
        $user = User::factory()->create();
        $xafOrder = $this->makeCompletedOrder($user, 'XAF', 4200.00, 600.0, 7.00);
        $this->attachItem($xafOrder, 4200.00, 'US');

        $byCountry = app(DashboardMetricsQuery::class)->getBestSellingCountries(7);

        $this->assertArrayHasKey('US', $byCountry);
        $this->assertEqualsWithDelta(7.00, $byCountry['US'], 0.01);
    }

    public function test_sales_cost_timeseries_reports_sales_in_usd(): void
    {
        $user = User::factory()->create();
        $xafOrder = $this->makeCompletedOrder($user, 'XAF', 4200.00, 600.0, 7.00);
        $this->attachItem($xafOrder, 4200.00);

        $series = app(DashboardMetricsQuery::class)->getSalesCostTimeseries(7);
        $today = collect($series)->firstWhere('date', now()->format('Y-m-d'));

        $this->assertNotNull($today);
        $this->assertEqualsWithDelta(7.00, $today['sales'], 0.01);
    }
}
