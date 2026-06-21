<?php

namespace Tests\Feature\Payment;

use App\Domain\Order\Services\GatewayRefundService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Wallet\Services\WalletService;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * When an order paid by a NON-wallet gateway (card / mobile money / crypto)
 * fails to fulfill, the customer's money must be returned to their wallet -
 * the wallet-first refund policy. Previously only wallet-funded orders were
 * reversed, so a MoMo customer was charged with no product and no refund.
 */
class GatewayRefundOnFailureTest extends TestCase
{
    use RefreshDatabase;

    private function failedMomoOrder(float $charged, string $currency = 'XAF', ?float $itemSubtotal = null): OrderItem
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'RSR-'.Str::upper(Str::random(8)),
            'payment_method' => 'flutterwave',
            'payment_status' => PaymentStatus::Paid,
            'order_status' => 'failed',
            'display_currency' => $currency,
            'total_amount' => $charged,
            'subtotal_amount' => $charged,
        ]);

        PaymentAttempt::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'flutterwave',
            'idempotency_key' => (string) Str::uuid(),
            'currency' => $currency,
            'amount' => $charged,
            'payment_status' => PaymentStatus::Paid,
            'gateway_reference' => 'FLW-'.Str::random(6),
            'confirmed_at' => now(),
        ]);

        $category = Category::factory()->create(['slug' => 'gift-cards']);
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        $product = Product::factory()->create(['category_id' => $category->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'zendit',
            'quantity' => 1,
            'display_currency' => $currency,
            'display_amount' => $charged,
            'provider_cost_usd' => 9.0,
            'markup_amount' => 0,
            'subtotal_amount' => $itemSubtotal ?? $charged,
            'product_snapshot' => ['name' => 'Test Gift Card'],
            'variant_snapshot' => ['face_value' => 10, 'currency' => 'USD'],
            'fulfillment_status' => 'failed',
            'failed_at' => now(),
        ]);
    }

    public function test_failed_momo_order_is_refunded_to_the_customer_wallet(): void
    {
        $item = $this->failedMomoOrder(6510.00, 'XAF'); // ~$10 in XAF

        app(GatewayRefundService::class)->refundFailedItemToWallet($item);

        $wallet = app(WalletService::class)->getOrCreateWallet($item->order->user, Currency::XAF);
        $this->assertEqualsWithDelta(6510.00, (float) $wallet->fresh()->balance, 0.01);

        $tx = WalletTransaction::where('user_id', $item->order->user_id)
            ->where('transaction_category', TransactionCategory::Refund)
            ->first();
        $this->assertNotNull($tx);
        $this->assertEqualsWithDelta(6510.00, (float) $tx->amount, 0.01);
    }

    public function test_refund_is_idempotent_across_retries(): void
    {
        $item = $this->failedMomoOrder(6510.00, 'XAF');

        $service = app(GatewayRefundService::class);
        $service->refundFailedItemToWallet($item);
        $service->refundFailedItemToWallet($item); // retry / second failure path
        $service->refundFailedItemToWallet($item);

        $wallet = app(WalletService::class)->getOrCreateWallet($item->order->user, Currency::XAF);
        // Credited exactly once despite three calls.
        $this->assertEqualsWithDelta(6510.00, (float) $wallet->fresh()->balance, 0.01);
        $this->assertSame(1, WalletTransaction::where('user_id', $item->order->user_id)->count());
    }

    public function test_wallet_paid_orders_are_left_to_the_wallet_reversal_path(): void
    {
        $item = $this->failedMomoOrder(10.00, 'USD');
        // Flip the attempt to a wallet payment - the gateway refund service
        // must NOT act (WalletPaymentProvider handles those).
        $item->order->paymentAttempts()->update(['gateway' => 'wallet']);

        app(GatewayRefundService::class)->refundFailedItemToWallet($item->fresh());

        $this->assertSame(0, WalletTransaction::where('user_id', $item->order->user_id)->count());
    }
}
