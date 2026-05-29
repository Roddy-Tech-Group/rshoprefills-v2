<?php

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Events\CartUpdated;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class CartManager
{
    public function __construct(
        private CartPricingService $pricingService,
        private CartValidationService $validationService
    ) {}

    /**
     * Get or create the active cart for the current session.
     */
    public function resolveCart(?int $userId = null, ?string $guestToken = null): Cart
    {
        if ($userId) {
            return Cart::firstOrCreate(
                ['user_id' => $userId, 'status' => 'active'],
                ['last_activity_at' => now()]
            );
        }

        if ($guestToken) {
            return Cart::firstOrCreate(
                ['guest_token' => $guestToken, 'status' => 'active'],
                ['last_activity_at' => now()]
            );
        }

        throw new \InvalidArgumentException('Must provide either a user ID or a guest token.');
    }

    /**
     * Add an item to the cart.
     *
     * @param  array<string, mixed>|null  $metadata  Per-item context (recipient
     *                                               phone for top-ups, delivery email for gift cards, etc.). Merged
     *                                               into cart_items.metadata_snapshot; later copied onto the order
     *                                               item at checkout so fulfilment providers can read it.
     */
    public function addItem(Cart $cart, ProductVariant $variant, int $quantity, ?float $requestedValue = null, ?array $metadata = null): CartItem
    {
        return DB::transaction(function () use ($cart, $variant, $quantity, $requestedValue, $metadata) {
            // 1. Validate addition
            $this->validationService->validateAddition($variant, $requestedValue);

            // 2. Check for existing identical variant in cart
            $existingItem = $cart->items()->where('product_variant_id', $variant->id)->first();

            // 3. Calculate pricing
            $pricing = $this->pricingService->calculatePricing($variant, $quantity);

            // For variable products (e.g. eSIMs or flexible Gift Cards), we might need to
            // override the subtotal/cost logic based on the requested face value.
            // Assuming for now that CartPricingService handles the variant's base configuration.
            // If requestedValue is passed, we overwrite display_amount and subtotal.
            if ($variant->is_variable && $requestedValue !== null) {
                // Simplified variable pricing: assume provider cost scales linearly
                // or we take a fixed percentage of face value.
                // This will need expansion based on provider specifics, but we'll
                // override display_amount for now.
                $pricing['display_amount'] = $requestedValue * $quantity;
                $pricing['subtotal_snapshot'] = $requestedValue * $quantity; // Assuming 1:1 for simplicity if not scaled
            }

            // Merge any incoming metadata over the snapshot we may already
            // have stored on the cart row — newer values win so re-adding
            // the same top-up with a corrected phone number replaces the
            // earlier entry without losing other keys.
            $mergedMeta = array_filter(array_merge(
                (array) ($existingItem?->metadata_snapshot ?? []),
                $metadata ?? [],
            ), fn ($v) => $v !== null && $v !== '');

            if ($existingItem) {
                // Update existing
                $newQuantity = $existingItem->quantity + $quantity;
                $newPricing = $this->pricingService->calculatePricing($variant, $newQuantity);

                if ($variant->is_variable && $requestedValue !== null) {
                    $newPricing['display_amount'] = $requestedValue * $newQuantity;
                    $newPricing['subtotal_snapshot'] = $requestedValue * $newQuantity;
                }

                $existingItem->update(array_merge([
                    'quantity' => $newQuantity,
                    'metadata_snapshot' => $mergedMeta ?: null,
                ], $newPricing));

                $item = $existingItem;
            } else {
                // Create new
                $item = $cart->items()->create(array_merge([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'display_currency' => $variant->currency ?? 'USD',
                    'metadata_snapshot' => $mergedMeta ?: null,
                ], $pricing));
            }

            $cart->touch('last_activity_at');

            // Dispatch event
            CartUpdated::dispatch($cart->fresh('items'));

            return $item;
        });
    }

    public function updateQuantity(Cart $cart, string $cartItemId, int $quantity): CartItem
    {
        $item = $cart->items()->findOrFail($cartItemId);

        if ($quantity <= 0) {
            $item->delete();
            CartUpdated::dispatch($cart->fresh('items'));

            return $item; // returning deleted item
        }

        $pricing = $this->pricingService->calculatePricing($item->variant, $quantity);

        // Handle variable products correctly if updating quantity
        if ($item->variant->is_variable) {
            $singleRequestedValue = $item->display_amount / $item->quantity;
            $pricing['display_amount'] = $singleRequestedValue * $quantity;
            $pricing['subtotal_snapshot'] = $singleRequestedValue * $quantity;
        }

        $item->update(array_merge(['quantity' => $quantity], $pricing));

        $cart->touch('last_activity_at');
        CartUpdated::dispatch($cart->fresh('items'));

        return $item;
    }

    public function removeItem(Cart $cart, string $cartItemId): void
    {
        $cart->items()->findOrFail($cartItemId)->delete();
        $cart->touch('last_activity_at');
        CartUpdated::dispatch($cart->fresh('items'));
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->touch('last_activity_at');
        CartUpdated::dispatch($cart->fresh('items'));
    }
}
