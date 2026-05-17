<?php

namespace App\Domain\Order\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Payment\Services\PaymentGatewayFactory;
use App\Domain\Payment\Providers\WalletPaymentProvider;
use App\Domain\Order\Events\OrderPlaced;
use App\Jobs\FulfillOrderItemJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        private readonly OrderValidationService $orderValidationService,
        private readonly PaymentGatewayFactory $paymentGatewayFactory,
        private readonly WalletPaymentProvider $walletPaymentProvider
    ) {}

    /**
     * Coordinate the checkout and order placement process.
     * Enforces row locking and atomic persistence.
     *
     * @throws \Exception
     */
    public function placeOrder(User $user, Cart $cart, string $paymentMethod, string $displayCurrency, ?string $deliveryEmail = null): Order
    {
        // 1. Recalculate and validate cart items/prices
        $validatedTotals = $this->orderValidationService->validateForCheckout($cart);

        // 2. Generate a readable, unique order number
        $orderNumber = 'RSR-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        // 3. Atomically build Order, snapshot items, and link Cart in a DB transaction
        $order = DB::transaction(function () use ($user, $cart, $paymentMethod, $displayCurrency, $validatedTotals, $orderNumber, $deliveryEmail) {
            
            // Re-lock the cart to avoid race conditions
            $lockedCart = Cart::where('id', $cart->id)->lockForUpdate()->firstOrFail();

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'cart_id' => $lockedCart->id,
                'settlement_currency' => 'USD',
                'display_currency' => $displayCurrency,
                'subtotal_amount' => $validatedTotals['subtotal'],
                'markup_amount' => $validatedTotals['total_markup'],
                'total_amount' => $validatedTotals['total'],
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_status' => FulfillmentStatus::NotStarted,
                'order_status' => OrderStatus::Pending,
                'placed_at' => now(),
                'metadata' => [
                    'delivery_email' => $deliveryEmail ?? $user->email,
                    'device_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            // Save order items with absolute snapshots
            foreach ($lockedCart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'category_id' => $item->product->category_id,
                    'subcategory_id' => $item->product->subcategory_id,
                    'provider_name' => $item->product->provider_name,
                    'provider_offer_id' => $item->variant->provider_offer_id,
                    'product_snapshot' => $item->product->toArray(),
                    'variant_snapshot' => $item->variant->toArray(),
                    'quantity' => $item->quantity,
                    'display_currency' => $displayCurrency,
                    'display_amount' => $item->display_amount,
                    'provider_cost_usd' => $item->provider_cost_usd,
                    'markup_amount' => $item->markup_amount,
                    'subtotal_amount' => $item->subtotal_snapshot,
                    'fulfillment_status' => FulfillmentStatus::NotStarted,
                ]);
            }

            // Deactivate or clear cart
            $lockedCart->items()->delete();
            $lockedCart->status = 'abandoned'; // Done with this cart
            $lockedCart->save();

            return $order;
        });

        // 4. Create the PaymentAttempt
        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => $paymentMethod,
            'idempotency_key' => 'PAY-' . Str::uuid()->toString(),
            'currency' => $displayCurrency,
            'amount' => $order->total_amount,
            'exchange_rate_snapshot' => 1.0000, // Display is same as settlement or map FX if needed
            'payment_status' => PaymentStatus::Pending,
        ]);

        OrderPlaced::dispatch($order);

        // 5. Initialize payment attempt via the selected gateway
        $provider = $this->paymentGatewayFactory->getProvider($paymentMethod);
        $initResult = $provider->initializePayment($attempt);

        // 6. Handle internal Wallet flow immediately (it reserves then triggers fulfillment)
        if ($paymentMethod === 'wallet') {
            DB::transaction(function () use ($order, $attempt) {
                // If reserved successfully
                $order->payment_status = PaymentStatus::Reserved;
                $order->order_status = OrderStatus::Processing;
                $order->save();
            });

            // Dispatch fulfillment immediately since funds are locked!
            foreach ($order->items as $item) {
                FulfillOrderItemJob::dispatch($item);
            }
        }

        return $order->load('paymentAttempts');
    }
}
