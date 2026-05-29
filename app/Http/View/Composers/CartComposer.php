<?php

namespace App\Http\View\Composers;

use App\Domain\Cart\Services\CartManager;
use App\Domain\Cart\Services\CartPricingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartComposer
{
    public function __construct(
        protected CartManager $cartManager,
        protected CartPricingService $pricingService
    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Try to resolve the cart based on session or guest token cookie
        $userId = Auth::id();
        $guestToken = request()->cookie('guest_token') ?? request()->header('X-Guest-Token');

        $cartCount = 0;
        $cartSubtotal = 0;
        $cartItems = collect();

        if ($userId || $guestToken) {
            try {
                $cart = $this->cartManager->resolveCart($userId, $guestToken);
                if ($cart && $cart->items->isNotEmpty()) {
                    $cart->load('items.product', 'items.variant');
                    $cartCount = $cart->items->sum('quantity');
                    $totals = $this->pricingService->calculateCartTotals($cart->items);
                    $cartSubtotal = $totals['subtotal'];
                    $cartItems = $cart->items;
                }
            } catch (\Exception $e) {
                // Ignore resolution errors for view rendering (e.g. invalid token)
            }
        }

        $view->with([
            'cartCount' => $cartCount,
            'cartSubtotal' => $cartSubtotal,
            'cartItems' => $cartItems,
        ]);
    }
}
