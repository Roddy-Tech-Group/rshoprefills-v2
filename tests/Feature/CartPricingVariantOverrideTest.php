<?php

namespace Tests\Feature;

use App\Domain\Cart\Services\CartPricingService;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPricingVariantOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_variant_retail_price_uses_override_when_set(): void
    {
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category']);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'provider_name' => 'test',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'po-test-1',
            'sku' => 'TEST-SKU',
            'currency' => 'USD',
            'face_value' => 10.00,
            'cost_price' => 3.00,
            'retail_price' => 4.50,
            'manual_retail_price_usd' => 9.99,
            'is_available' => true,
        ]);

        $service = new CartPricingService;

        // Override is set → must return the override verbatim, ignoring rules.
        $this->assertSame(9.99, $service->resolveVariantRetailPrice($variant, 3.00));
    }

    public function test_resolve_variant_retail_price_falls_back_to_rules_when_blank(): void
    {
        $category = Category::create(['name' => 'No-Override Category', 'slug' => 'no-override-cat']);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'No Override Product',
            'slug' => 'no-override',
            'provider_name' => 'test',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'po-test-2',
            'sku' => 'NO-OV-SKU',
            'currency' => 'USD',
            'face_value' => 10.00,
            'cost_price' => 3.00,
            'retail_price' => 4.50,
            'manual_retail_price_usd' => null,
            'is_available' => true,
        ]);

        $service = new CartPricingService;
        $price = $service->resolveVariantRetailPrice($variant, 3.00);

        // No override → rule chain applied → result is always above cost.
        $this->assertGreaterThan(3.00, $price);
    }

    public function test_coupon_apply_to_handles_percent_and_fixed(): void
    {
        $percent = Coupon::make([
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);
        $fixed = Coupon::make([
            'discount_type' => 'fixed',
            'discount_value' => 5,
        ]);

        $this->assertSame(9.0, $percent->applyTo(10.00));
        $this->assertSame(5.0, $fixed->applyTo(10.00));
    }

    public function test_coupon_apply_to_never_returns_negative(): void
    {
        $fixed = Coupon::make([
            'discount_type' => 'fixed',
            'discount_value' => 50, // larger than the sales price
        ]);

        $this->assertSame(0.0, $fixed->applyTo(10.00));
    }

    public function test_coupon_is_redeemable_respects_expiry_and_uses(): void
    {
        $active = Coupon::make([
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
            'used_count' => 0,
        ]);
        $this->assertTrue($active->isRedeemable());

        $expired = Coupon::make([
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
            'used_count' => 0,
            'valid_until' => now()->subDay(),
        ]);
        $this->assertFalse($expired->isRedeemable());

        $usedUp = Coupon::make([
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
            'max_uses' => 5,
            'used_count' => 5,
        ]);
        $this->assertFalse($usedUp->isRedeemable());

        $paused = Coupon::make([
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => false,
        ]);
        $this->assertFalse($paused->isRedeemable());
    }
}
