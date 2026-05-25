<?php

namespace App\Domain\Order\Services;

use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Events\OrderPlaced;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Providers\WalletPaymentProvider;
use App\Domain\Payment\Services\PaymentGatewayFactory;
use App\Domain\Payment\Services\PaymentSessionService;
use App\Jobs\FulfillOrderItemJob;
use App\Models\Cart;
use App\Models\CurrencyRate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly OrderValidationService $orderValidationService,
        private readonly PaymentGatewayFactory $paymentGatewayFactory,
        private readonly WalletPaymentProvider $walletPaymentProvider,
        private readonly PaymentSessionService $paymentSessionService
    ) {}

    /**
     * Coordinate the checkout and order placement process.
     * Enforces row locking and atomic persistence.
     *
     * @throws \Exception
     */
    public function placeOrder(User $user, Cart $cart, string $paymentMethod, string $displayCurrency, ?string $deliveryEmail = null): Order
    {
        // 1. Recalculate and validate cart items/prices (in raw USD)
        $validatedTotals = $this->orderValidationService->validateForCheckout($cart);

        // 1b. Resolve the display currency rate. The checkout page converts raw
        //     USD prices into the customer's chosen currency using this rate
        //     (e.g. USD × 1.04 = platform spread). The order must store the
        //     display-currency amount so the charge matches what was shown.
        $rate = CurrencyRate::resolve($displayCurrency);
        $exchangeRate = (float) $rate->rate_per_usd;

        // 2. Generate a readable, unique order number
        $orderNumber = 'RSR-'.date('Ymd').'-'.strtoupper(Str::random(6));

        // 2b. Cancel any earlier still-pending order for this cart so retrying the
        //     same cart does not pile up Pending rows for admins.
        Order::where('cart_id', $cart->id)
            ->where('order_status', OrderStatus::Pending)
            ->update(['order_status' => OrderStatus::Cancelled]);

        // 3. Atomically build Order + snapshot items. The cart is read-locked here
        //    but intentionally NOT deleted yet — see step 7. If the gateway init
        //    in step 5 throws (encryption key, bad currency, network), we want
        //    the cart to stay intact so the customer can retry without losing
        //    their items. Cart deletion now lives at the end, after init succeeds.
        $order = DB::transaction(function () use ($user, $cart, $paymentMethod, $displayCurrency, $validatedTotals, $orderNumber, $deliveryEmail, $exchangeRate) {

            // Re-lock the cart to avoid race conditions
            $lockedCart = Cart::where('id', $cart->id)->lockForUpdate()->firstOrFail();
            $lockedCart->load('items.product', 'items.variant');

            // Convert settlement USD totals into the display currency.
            $displaySubtotal = round($validatedTotals['subtotal'] * $exchangeRate, 4);
            $displayMarkup = round($validatedTotals['total_markup'] * $exchangeRate, 4);
            $displayTotal = round($validatedTotals['total'] * $exchangeRate, 4);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'cart_id' => $lockedCart->id,
                'settlement_currency' => $displayCurrency,
                'display_currency' => $displayCurrency,
                'subtotal_amount' => $displaySubtotal,
                'markup_amount' => $displayMarkup,
                'total_amount' => $displayTotal,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_status' => FulfillmentStatus::NotStarted,
                'order_status' => OrderStatus::Pending,
                'placed_at' => now(),
                'metadata' => [
                    'delivery_email' => $deliveryEmail ?? $user->email,
                    'device_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'exchange_rate' => $exchangeRate,
                    'settlement_subtotal_usd' => $validatedTotals['subtotal'],
                    'settlement_total_usd' => $validatedTotals['total'],
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
                    'provider_name' => $item->variant->metadata['provider'] ?? $item->product->provider_name,
                    'provider_offer_id' => $item->variant->provider_offer_id,
                    'product_snapshot' => $item->product->toArray(),
                    'variant_snapshot' => $item->variant->toArray(),
                    'quantity' => $item->quantity,
                    'display_currency' => $displayCurrency,
                    'display_amount' => round($item->display_amount * $exchangeRate, 4),
                    'provider_cost_usd' => $item->provider_cost_usd,
                    'markup_amount' => $item->markup_amount,
                    'subtotal_amount' => round($item->subtotal_snapshot * $exchangeRate, 4),
                    'fulfillment_status' => FulfillmentStatus::NotStarted,
                ]);
            }

            // NOTE: Cart deletion intentionally deferred to step 7 (post-init).

            return $order;
        });

        // 4. Create the PaymentAttempt
        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => $paymentMethod,
            'idempotency_key' => 'PAY-'.Str::uuid()->toString(),
            'currency' => $displayCurrency,
            'amount' => $order->total_amount,
            'exchange_rate_snapshot' => $exchangeRate,
            'payment_status' => PaymentStatus::Pending,
        ]);

        OrderPlaced::dispatch($order);

        // 5. Initialize payment attempt via the selected gateway. If this throws
        //    (encryption key invalid, currency unsupported, network down, etc.)
        //    the order goes to Failed and the exception re-raises — but the
        //    customer's cart is preserved (see step 3 comment) so they can fix
        //    the .env / pick another method and try again without re-adding items.
        try {
            $provider = $this->paymentGatewayFactory->getProvider($paymentMethod);
            $initResult = $provider->initializePayment($attempt);
        } catch (\Throwable $e) {
            $order->update([
                'order_status' => OrderStatus::Failed,
                'payment_status' => PaymentStatus::Failed,
            ]);
            $attempt->update([
                'payment_status' => PaymentStatus::Failed,
                'failed_at' => now(),
            ]);
            throw $e;
        }

        // 6. Create the PaymentSession
        $paymentSession = $this->paymentSessionService->createForOrder($order, $attempt, $initResult);

        // 7. The cart is intentionally NOT cleared here. It is cleared only when
        //    the payment is CONFIRMED (PaymentSessionService::confirmSession), so
        //    a failed or abandoned card/crypto payment keeps the cart for retry.

        // 7. Handle the internal wallet flow. When the wallet provider deferred for
        //    transaction-PIN authorization (status "awaiting_customer_action"), we do
        //    NOT touch funds here — the customer verifies their PIN on the frontend,
        //    which calls the pay endpoint to authorize, debit, and dispatch fulfillment.
        //    Only the no-PIN path settles synchronously below.
        if ($paymentMethod === 'wallet' && ($initResult['status'] ?? null) !== 'awaiting_customer_action') {
            DB::transaction(function () use ($order, $attempt, $paymentSession) {
                // Reserve → confirm → debit in one atomic transaction.
                $order->payment_status = PaymentStatus::Reserved;
                $order->order_status = OrderStatus::Processing;
                $order->save();

                // Wallet confirms instantly
                $this->paymentSessionService->confirmSession($paymentSession, ['transaction_id' => $attempt->gateway_reference]);

                // Actually debit the wallet (unlock reserved funds + deduct + record transaction).
                // Without this, funds stay locked but never deducted from the balance.
                $this->walletPaymentProvider->finalizeDebit($attempt);

                // Reflect the confirmed payment status on the order.
                $order->payment_status = PaymentStatus::Paid;
                $order->save();
            });

            // Dispatch fulfillment — funds are now fully debited.
            foreach ($order->items as $item) {
                FulfillOrderItemJob::dispatch($item);
            }
        }

        return $order->load(['paymentAttempts', 'paymentAttempts.paymentSession']);
    }
}
