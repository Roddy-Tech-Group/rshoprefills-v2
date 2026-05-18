<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\PaymentSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletFunding;
use App\Models\CurrencyRate;
use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\FundingStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmbeddedPaymentOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.zendit.api_key' => 'ZENDIT_API_KEY_MOCK',
            'services.flutterwave.secret_key' => 'FLW_SECRET_KEY_MOCK',
            'services.flutterwave.public_key' => 'FLW_PUB_KEY_MOCK',
            'services.nowpayments.api_key' => 'NOWPAYMENTS_KEY_MOCK',
        ]);

        $this->user = User::factory()->create();
        
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => Currency::USD,
            'balance' => 100.0,
        ]);

        CurrencyRate::create([
            'code' => 'USD',
            'name' => 'United States Dollar',
            'type' => 'fiat',
            'rate_per_usd' => 1.0,
            'is_active' => true,
        ]);
        
        CurrencyRate::create([
            'code' => 'NGN',
            'name' => 'Nigerian Naira',
            'type' => 'fiat',
            'rate_per_usd' => 1400.0,
            'is_active' => true,
        ]);
    }

    /**
     * Test initiating wallet funding returns structured payment session payload.
     */
    public function test_initiating_wallet_funding_returns_payment_session(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('api.wallets.fund.initiate'), [
            'currency' => 'USD',
            'amount' => 50.0,
            'display_currency' => 'NGN',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'payment_link',
                'payment_session' => [
                    'id',
                    'provider',
                    'session_type',
                    'status',
                    'client_reference',
                    'amount',
                    'currency',
                    'display_currency',
                    'payment_payload',
                    'expires_at',
                ],
                'reference',
                'requested_amount',
                'display_currency',
                'exchange_rate',
            ]);

        $sessionId = $response->json('payment_session.id');

        $this->assertDatabaseHas('payment_sessions', [
            'id' => $sessionId,
            'provider' => 'flutterwave',
            'status' => 'awaiting_payment',
            'amount' => 70000.0000, // 50 USD converted to NGN
            'display_currency' => 'USD',
        ]);
    }

    /**
     * Test polling payment session show and status endpoints.
     */
    public function test_can_poll_payment_session_show_and_status(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('api.wallets.fund.initiate'), [
            'currency' => 'USD',
            'amount' => 20.0,
            'display_currency' => 'USD',
        ]);

        $sessionId = $response->json('payment_session.id');

        // Test Show endpoint
        $showResponse = $this->actingAs($this->user)->getJson(route('api.payment-sessions.show', $sessionId));
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'awaiting_payment')
            ->assertJsonPath('data.provider', 'flutterwave');

        // Test Status poll endpoint
        $statusResponse = $this->actingAs($this->user)->getJson(route('api.payment-sessions.status', $sessionId));
        $statusResponse->assertStatus(200)
            ->assertExactJson([
                'id' => $sessionId,
                'status' => 'awaiting_payment',
                'is_expired' => false,
                'confirmed_at' => null,
                'failed_at' => null,
            ]);
    }

    /**
     * Test active payment session can be cancelled.
     */
    public function test_can_cancel_payment_session(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('api.wallets.fund.initiate'), [
            'currency' => 'USD',
            'amount' => 30.0,
            'display_currency' => 'USD',
        ]);

        $sessionId = $response->json('payment_session.id');

        $cancelResponse = $this->actingAs($this->user)->postJson(route('api.payment-sessions.cancel', $sessionId));
        $cancelResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Payment session cancelled successfully.',
                'status' => 'cancelled',
            ]);

        $this->assertDatabaseHas('payment_sessions', [
            'id' => $sessionId,
            'status' => 'cancelled',
        ]);

        $session = PaymentSession::find($sessionId);
        $this->assertDatabaseHas('payment_attempts', [
            'id' => $session->payment_attempt_id,
            'payment_status' => 'failed',
        ]);
    }

    /**
     * Test direct model transition to invalid state throws domain exception.
     */
    public function test_payment_session_state_machine_validation(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('api.wallets.fund.initiate'), [
            'currency' => 'USD',
            'amount' => 10.0,
            'display_currency' => 'USD',
        ]);

        $sessionId = $response->json('payment_session.id');
        $session = PaymentSession::find($sessionId);

        // Try direct transition pending -> confirmed (illegal)
        $session->status = 'pending';
        $session->save();

        $this->expectException(\DomainException::class);
        $session->transitionTo('confirmed');
    }

    /**
     * Test order checkout creates order and structured payment session.
     */
    public function test_order_checkout_generates_payment_session(): void
    {
        // 1. Setup storefront categories and product
        $category = Category::create(['name' => 'Gift Cards', 'slug' => 'gift-cards', 'type' => 'digital']);
        $subcategory = Subcategory::create(['category_id' => $category->id, 'name' => 'Gaming', 'slug' => 'gaming']);
        $product = Product::create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'zendit',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'Steam Gift Card',
            'slug' => 'steam-gift-card',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'steam-offer-10',
            'sku' => 'STEAM10',
            'currency' => 'USD',
            'face_value' => 10.00,
            'cost_price' => 9.50,
            'retail_price' => 10.50,
            'is_available' => true,
        ]);

        \Illuminate\Support\Facades\Cache::flush();

        \App\Models\PricingRule::create([
            'markup_type' => 'fixed',
            'markup_value' => 1.00,
            'is_active' => true,
        ]);

        $cart = Cart::create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'display_currency' => 'USD',
            'display_amount' => 10.50,
            'provider_cost_usd' => 9.50,
            'markup_amount' => 1.00,
            'unit_price_snapshot' => 10.50,
            'subtotal_snapshot' => 10.50,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('api.checkout.place-order'), [
            'cart_id' => $cart->id,
            'payment_method' => 'flutterwave',
            'preferred_currency' => 'USD',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'order' => ['id', 'order_number', 'total_amount'],
                'payment_session' => ['id', 'provider', 'session_type', 'status', 'payment_payload'],
            ]);

        $sessionId = $response->json('payment_session.id');
        $this->assertDatabaseHas('payment_sessions', [
            'id' => $sessionId,
            'provider' => 'flutterwave',
            'status' => 'awaiting_payment',
        ]);
    }
}
