<?php

namespace App\Domain\Cart\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

class CartValidationService
{
    public function __construct(private CartPricingService $pricingService) {}

    /**
     * Validate an entire cart, checking all items.
     * Returns an array of validation errors or warnings keyed by cart_item_id.
     */
    public function validateCart(Cart $cart): array
    {
        $issues = [];

        foreach ($cart->items as $item) {
            $itemIssues = $this->validateItem($item);
            if (! empty($itemIssues)) {
                $issues[$item->id] = $itemIssues;
            }
        }

        return $issues;
    }

    /**
     * Validate a single cart item against current product/variant state.
     */
    public function validateItem(CartItem $item): array
    {
        $issues = [];
        $variant = $item->variant;
        $product = $item->product;

        // 1. Check availability
        if (! $variant || ! $variant->is_available) {
            $issues[] = 'variant_unavailable';
        }

        if (! $product || ! $product->is_active) {
            $issues[] = 'product_unavailable';
        }

        if (! empty($issues)) {
            // If it's unavailable, no need to check pricing limits
            return $issues;
        }

        // 2. Check min/max limits for variable items
        if ($variant->is_variable) {
            // Variable items typically have display_amount mapping to face_value
            // Wait, for variable items, the user chooses a face value.
            // In Zendit, variable products have min_amount and max_amount.
            $requestedValue = (float) $item->display_amount; // Assuming display amount is the requested face value
            if ($variant->min_amount && $requestedValue < (float) $variant->min_amount) {
                $issues[] = 'below_minimum_amount';
            }
            if ($variant->max_amount && $requestedValue > (float) $variant->max_amount) {
                $issues[] = 'above_maximum_amount';
            }
        }

        // 3. Check pricing snapshot integrity — recalculate what the unit price
        // *should* be right now and compare against the snapshot.
        //
        // Money is compared with a 1-cent tolerance: a strict !== on floats
        // falsely flags economically-identical prices that differ only in their
        // binary representation (e.g. 11.01 is not exactly representable), which
        // would block every checkout. A genuine markup/catalog change shifts the
        // price by far more than a cent and is still caught.
        $currentPricing = $this->pricingService->calculatePricing($variant, $item->quantity);

        if (abs((float) $item->unit_price_snapshot - (float) $currentPricing['unit_price_snapshot']) > 0.01) {
            $issues[] = 'price_updated';
        }

        return $issues;
    }

    /**
     * Validates if a new item CAN be added to the cart.
     */
    public function validateAddition(ProductVariant $variant, ?float $requestedValue = null): void
    {
        $product = $variant->product;

        if (! $product || ! $product->is_active) {
            throw new \Exception('This product is no longer available.');
        }

        if (! $variant->is_available) {
            throw new \Exception('This specific variant is currently unavailable.');
        }

        if ($variant->is_variable && $requestedValue !== null) {
            if ($variant->min_amount && $requestedValue < (float) $variant->min_amount) {
                throw new \Exception("The minimum amount is {$variant->min_amount}.");
            }
            if ($variant->max_amount && $requestedValue > (float) $variant->max_amount) {
                throw new \Exception("The maximum amount is {$variant->max_amount}.");
            }
        }
    }
}
