<?php

namespace App\Domain\Order\Services;

use App\Domain\Cart\Services\CartPricingService;
use App\Domain\Cart\Services\CartValidationService;
use App\Models\Cart;

class OrderValidationService
{
    public function __construct(
        private readonly CartPricingService $cartPricingService,
        private readonly CartValidationService $cartValidationService
    ) {}

    /**
     * Validate a cart immediately before order creation.
     * Checks availability, min/max limits, and pricing snapshots freshness.
     *
     * @throws \Exception
     */
    public function validateForCheckout(Cart $cart): array
    {
        if ($cart->items->isEmpty()) {
            throw new \Exception('Cannot checkout an empty cart.');
        }

        // 1. Validate availability & min/max using the CartValidationService
        $issues = $this->cartValidationService->validateCart($cart);

        if (! empty($issues)) {
            // Flatten issues
            $allIssues = [];
            foreach ($issues as $itemId => $itemIssues) {
                $allIssues = array_merge($allIssues, $itemIssues);
            }
            if (! empty($allIssues)) {
                throw new \Exception('Cart contains invalid or unavailable items: '.implode(', ', array_unique($allIssues)));
            }
        }

        // 2. Recalculate totals to perform an integrity check on pricing drift
        $totals = $this->cartPricingService->calculateCartTotals($cart->items);

        return $totals;
    }
}
