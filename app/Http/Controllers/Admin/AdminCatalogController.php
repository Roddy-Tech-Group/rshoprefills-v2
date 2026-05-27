<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Jobs\SyncZenditEsimsJob;
use App\Jobs\SyncZenditGiftCardsJob;
use App\Jobs\SyncZenditTopupsJob;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCatalogController extends Controller
{
    public function syncZendit()
    {
        // Dispatch the job to sync the first page
        SyncZenditGiftCardsJob::dispatch(1);

        return response()->json([
            'message' => 'Zendit catalog sync job dispatched successfully.',
        ]);
    }

    public function syncZenditEsims()
    {
        SyncZenditEsimsJob::dispatch(1);

        return response()->json([
            'message' => 'Zendit eSIM catalog sync job dispatched successfully.',
        ]);
    }

    public function syncZenditTopups()
    {
        SyncZenditTopupsJob::dispatch(1);

        return response()->json([
            'message' => 'Zendit mobile top-up catalog sync job dispatched successfully.',
        ]);
    }

    public function products(Request $request)
    {
        $products = Product::with(['category', 'subcategory', 'variants'])
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

        return ProductResource::collection($products);
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return response()->json(['is_active' => $product->is_active]);
    }

    public function toggleFeatured(Product $product)
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        return response()->json(['is_featured' => $product->is_featured]);
    }

    public function togglePopular(Product $product)
    {
        $product->update(['is_popular' => ! $product->is_popular]);

        return response()->json(['is_popular' => $product->is_popular]);
    }

    /**
     * Set the admin sales-price override on a single variant. Bypasses the
     * pricing-rule chain — used when the admin wants a fixed USD price on this
     * SKU regardless of category/global markup rules.
     */
    public function setVariantPrice(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'manual_retail_price_usd' => ['required', 'numeric', 'min:0.01'],
        ]);

        $variant->update([
            'manual_retail_price_usd' => $validated['manual_retail_price_usd'],
        ]);

        return response()->json([
            'manual_retail_price_usd' => (float) $variant->manual_retail_price_usd,
        ]);
    }

    /**
     * Clear the override so pricing falls back to the rules chain.
     */
    public function clearVariantPrice(ProductVariant $variant)
    {
        $variant->update(['manual_retail_price_usd' => null]);

        return response()->json(['manual_retail_price_usd' => null]);
    }

    /**
     * List coupons attached to a variant. Returns redeemability state so the
     * drawer can render the right badge (active / expired / used-up).
     */
    public function listCoupons(ProductVariant $variant)
    {
        $coupons = $variant->coupons()->latest()->get()->map(fn (Coupon $c) => [
            'id' => $c->id,
            'code' => $c->code,
            'discount_type' => $c->discount_type,
            'discount_value' => (float) $c->discount_value,
            'max_uses' => $c->max_uses,
            'used_count' => $c->used_count,
            'valid_from' => $c->valid_from?->toIso8601String(),
            'valid_until' => $c->valid_until?->toIso8601String(),
            'is_active' => $c->is_active,
            'is_redeemable' => $c->isRedeemable(),
            'is_expired' => $c->isExpired(),
            'is_used_up' => $c->isUsedUp(),
        ]);

        return response()->json(['coupons' => $coupons]);
    }

    /**
     * Create a coupon for a variant. Code is uppercased + uniqueness-checked
     * across the whole table — a customer types ONE code at checkout, so it
     * can't collide with another variant's coupon.
     */
    public function createCoupon(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:64'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'valid_until' => ['nullable', 'date', 'after:now'],
        ]);

        $code = Str::upper(trim($validated['code']));

        if (Coupon::where('code', $code)->exists()) {
            return response()->json([
                'message' => 'That coupon code is already taken.',
                'errors' => ['code' => ['That coupon code is already taken.']],
            ], 422);
        }

        if ($validated['discount_type'] === 'percent' && $validated['discount_value'] > 100) {
            return response()->json([
                'message' => 'Percent discount cannot exceed 100.',
                'errors' => ['discount_value' => ['Percent discount cannot exceed 100.']],
            ], 422);
        }

        $coupon = $variant->coupons()->create([
            'code' => $code,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'max_uses' => $validated['max_uses'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'is_active' => true,
            'created_by' => (string) ($request->user()?->id ?? 'system'),
        ]);

        return response()->json([
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
                'max_uses' => $coupon->max_uses,
                'used_count' => $coupon->used_count,
                'valid_from' => $coupon->valid_from?->toIso8601String(),
                'valid_until' => $coupon->valid_until?->toIso8601String(),
                'is_active' => $coupon->is_active,
                'is_redeemable' => $coupon->isRedeemable(),
                'is_expired' => $coupon->isExpired(),
                'is_used_up' => $coupon->isUsedUp(),
            ],
        ]);
    }

    public function deleteCoupon(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json(['deleted' => true]);
    }
}
