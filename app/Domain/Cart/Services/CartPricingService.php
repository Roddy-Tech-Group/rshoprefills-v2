<?php

namespace App\Domain\Cart\Services;

use App\Models\CartItem;
use App\Models\PricingRule;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;

class CartPricingService
{
    /**
     * Calculate all pricing fields for a potential cart item.
     * Returns an array of pricing data to be snapshot on the CartItem.
     */
    public function calculatePricing(ProductVariant $variant, int $quantity): array
    {
        // 1. Determine base USD cost (assuming cost_price is already the provider's USD settlement cost)
        $providerCostUsd = (float) $variant->cost_price;

        // 2. Fetch applicable pricing rule
        $rule = $this->getApplicablePricingRule($variant);

        // 3. Calculate markup
        $markupAmount = 0;
        if ($rule) {
            if ($rule->markup_type === 'percentage') {
                $markupAmount = $providerCostUsd * ((float) $rule->markup_value / 100);
            } elseif ($rule->markup_type === 'fixed') {
                $markupAmount = (float) $rule->markup_value;
            }
        }

        // 4. Calculate unit retail price
        $unitPriceUsd = $providerCostUsd + $markupAmount;

        // 5. Calculate subtotal
        $subtotalUsd = $unitPriceUsd * $quantity;

        return [
            'provider_cost_usd' => $providerCostUsd,
            'markup_amount' => $markupAmount,
            'unit_price_snapshot' => $unitPriceUsd,
            'subtotal_snapshot' => $subtotalUsd,
            // Display layer (we assume checkout is in USD for now, frontend will convert later if needed)
            'display_currency' => 'USD',
            'display_amount' => $subtotalUsd,
            'exchange_rate_snapshot' => 1.0, // Since display is USD
        ];
    }

    /**
     * Finds the most specific pricing rule (Subcategory > Category).
     * Cached for performance.
     */
    protected function getApplicablePricingRule(ProductVariant $variant): ?PricingRule
    {
        $product = $variant->product;
        if (! $product) {
            return null;
        }

        $subcategoryId = $product->subcategory_id;
        $categoryId = $product->category_id;

        return Cache::remember("pricing_rule.{$categoryId}.{$subcategoryId}", 3600, function () use ($subcategoryId, $categoryId) {
            // Check subcategory first
            $rule = PricingRule::where('subcategory_id', $subcategoryId)
                ->where('is_active', true)
                ->first();

            if ($rule) {
                return $rule;
            }

            // Fallback to category
            return PricingRule::where('category_id', $categoryId)
                ->where('is_active', true)
                ->whereNull('subcategory_id')
                ->first();
        });
    }

    /**
     * Recalculate totals for an entire cart.
     */
    public function calculateCartTotals(iterable $items): array
    {
        $subtotal = 0;
        $totalMarkup = 0;
        $totalProviderCost = 0;

        foreach ($items as $item) {
            $subtotal += $item->subtotal_snapshot;
            $totalMarkup += ($item->markup_amount * $item->quantity);
            $totalProviderCost += ($item->provider_cost_usd * $item->quantity);
        }

        return [
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'total_markup' => $totalMarkup,
            'total_provider_cost' => $totalProviderCost,
            'total' => $subtotal, // No tax/discounts yet
        ];
    }
}
