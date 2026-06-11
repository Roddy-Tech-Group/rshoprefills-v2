<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Points earned" on the order page must come from the order's settlement
 * USD, not the display-currency total (a 1249.60 XAF order is ~$2, not 1249
 * points).
 */
class OrderPagePointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_points_earned_uses_settlement_usd_not_display_amount(): void
    {
        Setting::set('rcoin_enabled', true);
        Setting::set('cashback_percentage', 1.0);
        Setting::set('rcoin_usd_rate', 0.01);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'RSR-TEST-POINTS1',
            'cart_id' => null,
            'settlement_currency' => 'XAF',
            'display_currency' => 'XAF',
            'subtotal_amount' => 1249.60,
            'markup_amount' => 0,
            'total_amount' => 1249.60,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'fulfillment_status' => 'processing',
            'order_status' => 'processing',
            'placed_at' => now(),
            'metadata' => ['exchange_rate' => 625.0, 'settlement_total_usd' => 2.00, 'settlement_subtotal_usd' => 2.00],
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('shop.order', $order->order_number));

        $response->assertOk();
        $response->assertSee('Points earned');
        // $2 x 1% = $0.02 = 2 Rcoin at the $0.01 rate - never 1,249 (the
        // display-amount bug). The total itself prints "1,249.60", so target
        // the points span exactly.
        $response->assertSee('tabular-nums text-zinc-900">2</span>', false);
        $response->assertDontSee('text-zinc-900">1,249</span>', false);
    }

    public function test_status_probe_returns_order_state_to_the_owner_only(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $stranger = User::factory()->create(['email_verified_at' => now()]);

        $order = Order::create([
            'user_id' => $owner->id,
            'order_number' => 'RSR-TEST-STATUS1',
            'cart_id' => null,
            'settlement_currency' => 'USD',
            'display_currency' => 'USD',
            'subtotal_amount' => 5,
            'markup_amount' => 0,
            'total_amount' => 5,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'fulfillment_status' => 'processing',
            'order_status' => 'processing',
            'placed_at' => now(),
            'metadata' => ['exchange_rate' => 1.0, 'settlement_total_usd' => 5.00, 'settlement_subtotal_usd' => 5.00],
        ]);

        $this->actingAs($owner)
            ->getJson(route('shop.order.status', $order->order_number))
            ->assertOk()
            ->assertJson(['order_status' => 'processing', 'fulfillment_status' => 'processing']);

        // The watcher endpoint must not let order numbers be enumerated.
        $this->actingAs($stranger)
            ->getJson(route('shop.order.status', $order->order_number))
            ->assertNotFound();
    }
}
