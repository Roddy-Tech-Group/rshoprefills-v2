<?php

namespace App\Http\Controllers;

use App\Domain\Cart\Services\CartManager;
use App\Domain\Cart\Services\CartPricingService;
use App\Models\Cart;
use App\Models\CurrencyRate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Storefront-facing cart endpoints.
 *
 * The backend's API CartController is stateless (guest-token only) — a logged-in
 * web customer's session cookie won't authenticate it without Sanctum SPA config.
 * These WEB routes carry the session cookie, so the cart ties to the user_id for
 * authed customers and to a `guest_token` cookie for guests. Both paths delegate
 * to the same backend CartManager service; only the auth/transport differs.
 *
 * Responses are a compact JSON shape the storefront's Alpine cart store consumes.
 */
class CartWebController extends Controller
{
    public function __construct(
        private CartManager $cartManager,
        private CartPricingService $pricing,
    ) {}

    /**
     * The cart page (HTML). Store-driven — the Alpine cart store hydrates it
     * from the /cart/data JSON endpoint, so no data is passed server-side.
     */
    public function page()
    {
        return view('shop.cart');
    }

    public function show(Request $request)
    {
        [$cart, $token] = $this->resolve($request);

        return $this->respond($cart, $token, $request->query('currency'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'requested_value' => ['nullable', 'numeric', 'min:0.01'],
            'metadata' => ['nullable', 'array'],
            // Top-up recipient phone. Stored on cart_items.metadata_snapshot
            // and copied onto order_items.metadata at checkout so the Zendit
            // top-up fulfilment provider can read it.
            'metadata.recipient_phone' => ['nullable', 'string', 'regex:/^\+?[0-9 \-]{6,20}$/'],
            'metadata.delivery_email' => ['nullable', 'email'],
            // Bill payment account / meter ID. Alphanumeric + common separators,
            // 4-30 chars (covers electricity meters, DSTV smartcards, etc.).
            'metadata.account_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9 \-]{4,30}$/'],
        ]);

        [$cart, $token] = $this->resolve($request);
        $variant = ProductVariant::findOrFail($data['product_variant_id']);

        $this->cartManager->addItem(
            $cart,
            $variant,
            (int) $data['quantity'],
            isset($data['requested_value']) ? (float) $data['requested_value'] : null,
            $data['metadata'] ?? null,
        );

        return $this->respond($cart, $token, $request->query('currency'));
    }

    public function update(Request $request, string $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        [$cart, $token] = $this->resolve($request);
        $this->cartManager->updateQuantity($cart, $item, (int) $data['quantity']);

        return $this->respond($cart, $token, $request->query('currency'));
    }

    public function remove(Request $request, string $item)
    {
        [$cart, $token] = $this->resolve($request);
        $this->cartManager->removeItem($cart, $item);

        return $this->respond($cart, $token, $request->query('currency'));
    }

    /**
     * Resolve the active cart for this request. Authed users key by user_id;
     * guests key by a `guest_token` cookie (minted here if absent).
     *
     * @return array{0: Cart, 1: string|null} [cart, guestTokenToSet]
     */
    private function resolve(Request $request): array
    {
        $userId = auth()->id();
        $token = $request->cookie('guest_token');

        if (! $userId && ! $token) {
            $token = (string) Str::uuid();
        }

        $cart = $this->cartManager->resolveCart($userId, $userId ? null : $token);

        return [$cart, $userId ? null : $token];
    }

    /**
     * Compact JSON the cart store + popup render from. Queues the guest_token
     * cookie when the request belongs to a guest.
     *
     * Pricing is two-layered: the settlement figures (*_usd) are the marked-up USD
     * prices snapshotted on the CartItem; the display figures are those converted
     * into the customer's chosen currency via the live CurrencyRate. USD is the
     * base — every currency, USD included, is multiplied by its rate_per_usd.
     */
    private function respond(Cart $cart, ?string $guestToken, ?string $currencyCode)
    {
        $cart->load('items.product.category', 'items.variant');
        $totals = $this->pricing->calculateCartTotals($cart->items);

        $rate = CurrencyRate::resolve($currencyCode);

        $items = $cart->items->map(function ($item) use ($rate) {
            $product = $item->product;
            $variant = $item->variant;
            $name = $product
                ? Product::brandDisplayName($product->brand_key)
                : ($item->metadata_snapshot['product_name'] ?? 'Item');

            $unitUsd = (float) $item->unit_price_snapshot;
            $lineUsd = (float) $item->subtotal_snapshot;

            return [
                'id' => $item->id,
                'name' => $name,
                'country' => $product?->country_code,
                'logo' => $product ? Product::brandLogoUrl($product->brand_key, $product->logo_url) : null,
                'face_label' => $this->faceLabel($variant),
                'quantity' => (int) $item->quantity,
                'unit_price_usd' => round($unitUsd, 2),
                'unit_price' => round($rate->convert($unitUsd), 2),
                'line_total_usd' => round($lineUsd, 2),
                'line_total' => round($rate->convert($lineUsd), 2),
                // Category drives per-item copy on the cart + checkout pages
                // (gift-card region notice, top-up phone hint, eSIM QR hint).
                'category_slug' => $product?->category?->slug,
                'recipient_phone' => $item->metadata_snapshot['recipient_phone'] ?? null,
            ];
        })->values();

        $subtotalUsd = (float) ($totals['subtotal'] ?? 0);

        $response = response()->json([
            'count' => (int) $cart->items->sum('quantity'),
            'currency' => $rate->code,
            'currency_symbol' => Product::currencySymbol($rate->code),
            'rate' => (float) $rate->rate_per_usd,
            'subtotal_usd' => round($subtotalUsd, 2),
            'subtotal' => round($rate->convert($subtotalUsd), 2),
            'items' => $items,
        ]);

        if ($guestToken) {
            $response->cookie('guest_token', $guestToken, 60 * 24 * 30); // 30 days
        }

        return $response;
    }

    /**
     * The card's native face value as a short label, e.g. "£10". Null for
     * variable-amount cards (no fixed face value) so the UI can omit it.
     */
    private function faceLabel(?ProductVariant $variant): ?string
    {
        if (! $variant || $variant->face_value === null) {
            return null;
        }

        $value = (float) $variant->face_value;
        $valueStr = $value === floor($value)
            ? (string) (int) $value
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return Product::currencySymbol($variant->currency ?: 'USD').$valueStr;
    }
}
