<?php

namespace App\Http\Controllers;

use App\Domain\Cart\Services\CartManager;
use App\Domain\Cart\Services\CartPricingService;
use App\Domain\Shared\Enums\OrderStatus;
use App\Domain\Shared\Enums\PaymentGateway;
use App\Domain\Shared\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a cart into an order.
 *
 * process() creates the Order + OrderItem rows and a PENDING Payment, then
 * marks the cart converted. The actual gateway hand-off (Flutterwave hosted
 * page, NowPayments invoice, or wallet debit) is the marked TODO — it needs
 * gateway credentials/SDKs which live in backend infrastructure.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private CartManager $cartManager,
        private CartPricingService $pricing,
    ) {}

    public function process(Request $request)
    {
        $data = $request->validate([
            'delivery_email' => ['required', 'email'],
            'payment_method' => ['required', 'in:card,mobile_money,crypto,wallet'],
        ]);

        $user = $request->user();
        abort_unless($user, 403);

        $cart = $this->cartManager->resolveCart($user->id);
        $cart->load('items.product', 'items.variant');

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.checkout')
                ->with('checkout_status', 'Your cart is empty.');
        }

        $totals = $this->pricing->calculateCartTotals($cart->items);

        // Map the UI payment choice onto the backend PaymentGateway enum.
        $gateway = match ($data['payment_method']) {
            'crypto' => PaymentGateway::NowPayments,
            'wallet' => PaymentGateway::Wallet,
            default => PaymentGateway::Flutterwave, // card + mobile_money
        };

        $order = DB::transaction(function () use ($cart, $user, $totals, $data, $gateway) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'RSR-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
                'status' => OrderStatus::Pending,
                'subtotal' => $totals['subtotal'] ?? 0,
                'tax' => 0,
                'total' => $totals['total'] ?? 0,
                'currency' => $totals['currency'] ?? 'USD',
                'metadata' => [
                    'delivery_email' => $data['delivery_email'],
                    'payment_method' => $data['payment_method'],
                ],
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_type' => 'gift_card',
                    'product_id' => (string) $item->product_id,
                    'product_name' => $product
                        ? Product::brandDisplayName($product->brand_key)
                        : ($item->metadata_snapshot['product_name'] ?? 'Gift Card'),
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price_snapshot,
                    'total_price' => $item->subtotal_snapshot,
                    'currency' => $item->display_currency ?? 'USD',
                    'fulfillment_status' => 'pending',
                    'metadata' => [
                        'product_variant_id' => $item->product_variant_id,
                    ],
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'gateway' => $gateway,
                'status' => PaymentStatus::Pending,
                'amount' => $totals['total'] ?? 0,
                'currency' => $totals['currency'] ?? 'USD',
            ]);

            // The cart has been consumed by this order.
            $cart->update(['status' => 'converted']);

            return $order;
        });

        // TODO(backend): hand the pending Payment to its gateway and redirect the
        // customer to the hosted payment page:
        //   Flutterwave  → card / mobile_money
        //   NowPayments  → crypto (returns a wallet address + amount)
        //   Wallet       → debit a WalletTransaction here, no redirect
        // The gateway webhook then flips Payment + Order status to completed and
        // triggers Zendit fulfillment of each OrderItem.

        return redirect()->route('shop.order', $order->order_number)
            ->with('checkout_status', 'Order placed. Payment gateway hand-off is the next backend step.');
    }

    /**
     * Order confirmation page. Scoped to the owning user so order numbers
     * can't be enumerated by other customers.
     */
    public function order(Request $request, string $orderNumber)
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('user_id', $request->user()?->id)
            ->with(['items', 'payments'])
            ->firstOrFail();

        return view('shop.order', ['order' => $order]);
    }
}
