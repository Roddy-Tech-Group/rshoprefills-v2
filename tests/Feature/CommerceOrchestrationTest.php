<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Wallet;
use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Jobs\FulfillOrderItemJob;
use App\Jobs\VerifyPaymentJob;
use App\Domain\Payment\Providers\WalletPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommerceOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProductVariant $variant;
    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin service keys to mock values to guarantee offline/mock test execution
        config([
            'services.zendit.api_key' => 'ZENDIT_API_KEY_MOCK',
            'services.flutterwave.secret_key' => 'FLW_SECRET_KEY_MOCK',
            'services.nowpayments.api_key' => 'NOWPAYMENTS_KEY_MOCK',
            'pricing.safety_markup_percent' => 10.0,
            'pricing.min_margin_percent' => 1.0,
        ]);

        $this->user = User::factory()->create();

        // Establish Catalog Category/Subcategory/Product/Variant
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

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'steam-offer-10',
            'sku' => 'STEAM10',
            'currency' => 'USD',
            'face_value' => 10.00,
            'cost_price' => 9.50,
            'retail_price' => 10.50,
            'is_available' => true,
        ]);

        // Clear pricing resolver cache
        \Illuminate\Support\Facades\Cache::flush();

        // Establish pricing rule matching exactly the cart item's price
        \App\Models\PricingRule::create([
            'markup_type' => 'fixed',
            'markup_value' => 1.00,
            'is_active' => true,
        ]);

        // Establish a Cart
        $this->cart = Cart::create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        // face_value ($10) + fixed $1 markup = $11 unit price.
        // markup_amount is the per-unit rule markup ($1.00), stored on the cart
        // item for snapshot purposes.
        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'display_currency' => 'USD',
            'display_amount' => 11.00,
            'provider_cost_usd' => 9.50,
            'markup_amount' => 1.00,
            'unit_price_snapshot' => 11.00,
            'subtotal_snapshot' => 11.00,
        ]);
    }

    public function test_full_wallet_pin_authorization_reserves_funds_and_dispatches_fulfillment(): void
    {
        Queue::fake([FulfillOrderItemJob::class]);

        app(\App\Domain\Wallet\Services\TransactionPinService::class)->setupPin($this->user, '5283');

        // Setup Wallet with sufficient balance
        $wallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => Currency::USD,
            'balance' => 100.00,
            'locked_balance' => 0.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.checkout.place-order'), [
                'cart_id' => $this->cart->id,
                'payment_method' => 'wallet',
                'preferred_currency' => 'USD',
                'delivery_email' => 'customer@rshop.com',
            ]);

        $response->assertStatus(201);
        $order = Order::first();
        $this->assertEquals(OrderStatus::Pending, $order->order_status);

        // Authorize with PIN
        $verify = $this->actingAs($this->user)->postJson(route('api.wallets.pin.verify'), ['pin' => '5283'])->assertOk();
        $token = $verify->json('auth_token');

        $session = $order->paymentAttempts->first()->paymentSession;
        $this->actingAs($this->user)->postJson(route('api.payment-sessions.pay', $session->id), [
            'method' => 'wallet',
            'details' => ['auth_token' => $token]
        ])->assertOk();

        // Verify order persistence
        $order->refresh();
        $this->assertEquals(OrderStatus::Processing, $order->order_status);
        $this->assertEquals(PaymentStatus::Paid, $order->payment_status);

        // Verify Wallet balance locks
        $wallet->refresh();
        $this->assertEquals(100.00, (float)$wallet->balance);
        $this->assertEquals(11.00, (float)$wallet->locked_balance);

        // Verify Cart deactivation now that payment is confirmed
        $cart = Cart::find($this->cart->id);
        $this->assertEquals('abandoned', $cart->status);

        // Verify fulfillment job was dispatched immediately
        Queue::assertPushed(FulfillOrderItemJob::class, 1);
    }

    public function test_async_job_fulfills_order_item_and_completes_wallet_debit(): void
    {
        // Setup Wallet
        $wallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => Currency::USD,
            'balance' => 100.00,
            'locked_balance' => 0.00,
            'is_active' => true,
        ]);

        app(\App\Domain\Wallet\Services\TransactionPinService::class)->setupPin($this->user, '5283');

        // Place order directly via service
        $order = app(\App\Domain\Order\Services\CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'wallet',
            displayCurrency: 'USD'
        );

        $attempt = $order->paymentAttempts->first();
        $token = app(\App\Domain\Wallet\Services\TransactionPinService::class)->verifyPin($this->user, '5283');
        app(\App\Domain\Payment\Providers\WalletPaymentProvider::class)->authorizeTransaction($attempt, $token);

        $orderItem = $order->items->first();
        $this->assertEquals(FulfillmentStatus::NotStarted, $orderItem->fulfillment_status);

        // Execute FulfillOrderItemJob synchronously
        $job = new FulfillOrderItemJob($orderItem);
        $job->handle(
            app(\App\Domain\Fulfillment\Services\FulfillmentProviderFactory::class),
            app(\App\Domain\Order\Services\OrderService::class),
            app(WalletPaymentProvider::class)
        );

        // Verify item is fulfilled with pins
        $orderItem->refresh();
        $this->assertEquals(FulfillmentStatus::Fulfilled, $orderItem->fulfillment_status);
        $this->assertNotNull($orderItem->fulfillment_payload['pins']);

        // Verify wallet debit finalized
        $wallet->refresh();
        $this->assertEquals(89.00, (float)$wallet->balance);
        $this->assertEquals(0.00, (float)$wallet->locked_balance);

        // Verify order completed
        $order->refresh();
        $this->assertEquals(OrderStatus::Completed, $order->order_status, "Order Status: {$order->order_status->value}, Fulfillment Status: {$order->fulfillment_status->value}, Payment Status: {$order->payment_status->value}");
        $this->assertEquals(PaymentStatus::Paid, $order->payment_status);
    }

    public function test_checkout_via_external_gateway_returns_checkout_links(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('api.checkout.place-order'), [
                'cart_id' => $this->cart->id,
                'payment_method' => 'flutterwave',
                'preferred_currency' => 'USD',
            ]);

        $this->assertEquals(201, $response->status(), $response->content());
        $response->assertJsonStructure([
            'message',
            'order' => [
                'id',
                'order_number',
                'checkout_url',
            ]
        ]);

        $order = Order::first();
        $this->assertEquals(OrderStatus::Pending, $order->order_status);
        $this->assertEquals(PaymentStatus::Unpaid, $order->payment_status);
    }

    public function test_flutterwave_webhook_triggers_verification_and_fulfillment(): void
    {
        Queue::fake([VerifyPaymentJob::class]);

        // Place external order first
        $order = app(\App\Domain\Order\Services\CheckoutService::class)->placeOrder(
            user: $this->user,
            cart: $this->cart,
            paymentMethod: 'flutterwave',
            displayCurrency: 'USD'
        );

        $attempt = $order->paymentAttempts->first();

        config(['services.flutterwave.webhook_hash' => 'FLW_SECRET_HASH_MOCK']);

        // Simulate secure webhook trigger
        $response = $this->postJson(route('api.webhooks.flutterwave'), [
            'status' => 'successful',
            'txRef' => $attempt->idempotency_key,
            'id' => 'FLW-TEST-123',
            'amount' => 11.00,
            'currency' => 'USD',
        ], [
            'verif-hash' => 'FLW_SECRET_HASH_MOCK',
        ]);

        $response->assertStatus(200);

        // Verify verification job was dispatched
        Queue::assertPushed(VerifyPaymentJob::class);
    }
}
