<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Domain\Order\Services\CheckoutService;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutApiController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {}

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'uuid', 'exists:carts,id'],
            'payment_method' => ['required', 'string', Rule::in(['wallet', 'flutterwave', 'crypto'])],
            'preferred_currency' => ['required', 'string', 'size:3'],
            'delivery_email' => ['nullable', 'email'],
        ]);

        $cart = Cart::with(['items.product', 'items.variant'])->findOrFail($validated['cart_id']);

        try {
            $order = $this->checkoutService->placeOrder(
                user: $request->user(),
                cart: $cart,
                paymentMethod: $validated['payment_method'],
                displayCurrency: strtoupper($validated['preferred_currency']),
                deliveryEmail: $validated['delivery_email'] ?? null
            );

            return response()->json([
                'message' => 'Order placed successfully.',
                'order' => new OrderResource($order->load('items')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Checkout failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function index(Request $request)
    {
        $orders = Order::with(['items', 'paymentAttempts'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, string $orderNumber)
    {
        $order = Order::with(['items', 'paymentAttempts'])
            ->where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return new OrderResource($order);
    }
}
