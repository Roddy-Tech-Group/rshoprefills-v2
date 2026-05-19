<?php

namespace App\Http\Controllers;

use App\Domain\Cart\Services\CartManager;
use App\Domain\Order\Services\CheckoutService;
use App\Models\Order;
use Illuminate\Http\Request;
use Throwable;

/**
 * Web checkout. Delegates order placement to the CheckoutService orchestration
 * engine (the same path CheckoutApiController uses): cart validation, atomic
 * Order + OrderItem snapshots, a PaymentAttempt, gateway init, and — for wallet
 * payments — an immediate pessimistic-lock reserve + fulfillment dispatch.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private CartManager $cartManager,
        private CheckoutService $checkoutService,
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

        // The UI offers card / mobile_money / crypto / wallet; the CheckoutService
        // engine speaks wallet / flutterwave / crypto — card + mobile money both
        // settle through Flutterwave.
        $paymentMethod = match ($data['payment_method']) {
            'wallet' => 'wallet',
            'crypto' => 'crypto',
            default => 'flutterwave',
        };

        // Display currency comes from the customer's locale (hidden field on the
        // checkout form). Settlement is always USD; this is presentation only.
        $displayCurrency = strtoupper((string) $request->input('currency', 'USD'));
        if (strlen($displayCurrency) !== 3) {
            $displayCurrency = 'USD';
        }

        try {
            $order = $this->checkoutService->placeOrder(
                user: $user,
                cart: $cart,
                paymentMethod: $paymentMethod,
                displayCurrency: $displayCurrency,
                deliveryEmail: $data['delivery_email'],
            );
        } catch (Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Checkout could not be completed: '.$e->getMessage()
                ], 422);
            }
            return redirect()->route('shop.checkout')
                ->with('checkout_status', 'Checkout could not be completed: '.$e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            $session = $order->paymentAttempts()->latest()->first()?->paymentSession;
            return response()->json([
                'order_number' => $order->order_number,
                'redirect_url' => route('shop.order', $order->order_number),
                'payment_session' => $session ? new \App\Http\Resources\PaymentSessionResource($session) : null
            ]);
        }

        return redirect()->route('shop.order', $order->order_number);
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
            ->with(['items', 'paymentAttempts.paymentSession'])
            ->firstOrFail();

        return view('shop.order', ['order' => $order]);
    }
}
