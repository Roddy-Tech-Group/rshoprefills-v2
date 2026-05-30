<?php

namespace App\Http\Controllers;

use App\Domain\Cart\Services\CartManager;
use App\Domain\Cart\Services\CartPricingService;
use App\Domain\Fraud\Services\FraudDetectionService;
use App\Domain\Order\Exceptions\InvalidCouponException;
use App\Domain\Order\Services\CheckoutService;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Exceptions\MissingTransactionPinException;
use App\Domain\Wallet\Exceptions\WalletOnHoldException;
use App\Http\Resources\PaymentSessionResource;
use App\Models\Order;
use App\Models\Setting;
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
            'payment_method' => ['required', 'in:card,mobile_money,crypto,wallet,bank_transfer,apple_pay'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();
        abort_unless($user, 403);

        // Optional hard-gate: when the compliance toggle is ON, the buyer must
        // have a verified email address before they can pay. Soft by default
        // so the storefront stays browseable without verification friction.
        if (Setting::get('require_email_verified_for_checkout', false) && ! $user->hasVerifiedEmail()) {
            $msg = 'Please verify your email address before completing checkout. Check your inbox for the verification link.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $msg, 'redirect' => route('verification.notice')], 403);
            }

            return redirect()->route('verification.notice')->with('checkout_status', $msg);
        }

        $cart = $this->cartManager->resolveCart($user->id);
        $cart->load('items.product', 'items.variant');

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.checkout')
                ->with('checkout_status', 'Your cart is empty.');
        }

        $fraudService = app(FraudDetectionService::class);
        $cartTotals = app(CartPricingService::class)->calculateCartTotals($cart->items);
        $amount = $cartTotals['total'];

        // Turnstile Validation
        if (config('services.turnstile.enabled') && config('services.turnstile.enforce_checkout', true)) {
            $service = \App\Domain\Security\Services\TurnstileService::make();
            $result = $service->validateToken($request->input('cf-turnstile-response'), $request->ip());

            if ($result['status'] === \App\Domain\Security\Services\TurnstileService::STATUS_TIMEOUT) {
                // Fail OPEN conditionally: block if fraud risk is elevated
                if ($fraudService->isSuspiciousCheckout($user, $amount, $request->ip())) {
                    $msg = 'Security verification service is temporarily unavailable and transaction risk is elevated. Please try again later.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['message' => $msg], 403);
                    }
                    return redirect()->route('shop.checkout')->with('checkout_status', $msg);
                }
            } elseif ($result['status'] !== \App\Domain\Security\Services\TurnstileService::STATUS_SUCCESS && $result['status'] !== \App\Domain\Security\Services\TurnstileService::STATUS_BYPASSED) {
                $fraudService->recordTurnstileFailure($request->ip());
                $msg = 'Security verification failed. Please refresh the page and try again.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => $msg], 422);
                }
                return redirect()->route('shop.checkout')->with('checkout_status', $msg);
            }
        }

        // Display currency comes from the customer's locale (hidden field on the
        // checkout form). Settlement is always USD; this is presentation only.
        $displayCurrency = strtoupper((string) $request->input('currency', 'USD'));
        if (strlen($displayCurrency) !== 3) {
            $displayCurrency = 'USD';
        }

        // Verify currency-method compatibility behind the scenes
        $supported = [
            'USD' => ['card', 'apple_pay', 'crypto', 'wallet'],
            'EUR' => ['card', 'apple_pay', 'crypto', 'wallet'],
            'GBP' => ['card', 'apple_pay', 'crypto', 'wallet'],
            'NGN' => ['card', 'apple_pay', 'bank_transfer', 'crypto', 'wallet'],
            'GHS' => ['card', 'apple_pay', 'mobile_money', 'crypto', 'wallet'],
            'XAF' => ['card', 'apple_pay', 'mobile_money', 'crypto', 'wallet'],
            'XOF' => ['card', 'apple_pay', 'mobile_money', 'crypto', 'wallet'],
        ];

        $allowedMethods = $supported[$displayCurrency] ?? ['card', 'apple_pay'];
        if (! in_array($data['payment_method'], $allowedMethods)) {
            $msg = "The selected payment method is not available for {$displayCurrency}.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->route('shop.checkout')
                ->with('checkout_status', $msg);
        }

        // Map checkout page methods to backend payment providers
        $paymentMethod = match ($data['payment_method']) {
            'wallet' => 'wallet',
            'crypto' => 'crypto',
            default => 'flutterwave',
        };

        if ($fraudService->isSuspiciousCheckout($user, $amount, $request->ip())) {
            $msg = 'Your checkout attempt was flagged by our security systems. Please contact support.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $msg], 403);
            }

            return redirect()->route('shop.checkout')->with('checkout_status', $msg);
        }

        try {
            $order = $this->checkoutService->placeOrder(
                user: $user,
                cart: $cart,
                paymentMethod: $paymentMethod,
                displayCurrency: $displayCurrency,
                deliveryEmail: $data['delivery_email'],
                // Three states for Rcoin redemption:
                //   - 'full' -> pay the entire order with Rcoin (rewards-page convert flow)
                //   - true   -> standard partial redemption capped at redemption_max_percentage
                //   - false  -> no Rcoin
                applyRcoin: $request->input('apply_rcoin') === 'full' ? 'full' : $request->boolean('apply_rcoin'),
                couponCode: $data['coupon_code'] ?? null,
            );

            $fraudService->recordCheckout($user, $request->ip());
        } catch (InvalidCouponException $e) {
            // Coupon error - surface the customer-safe message verbatim so the
            // buyer sees "That coupon code is not valid" instead of a generic
            // checkout failure.
            $message = $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('shop.checkout')->with('checkout_status', $message);
        } catch (WalletOnHoldException|InsufficientBalanceException|MissingTransactionPinException $e) {
            // Customer-facing wallet errors carry their own polished message —
            // surface them verbatim so the user sees the "wallet on hold /
            // contact support" copy instead of a generic checkout failure.
            $message = $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('shop.checkout')->with('checkout_status', $message);
        } catch (Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Checkout could not be completed: '.$e->getMessage(),
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
                'payment_session' => $session ? new PaymentSessionResource($session) : null,
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
