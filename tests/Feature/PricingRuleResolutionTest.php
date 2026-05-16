<?php

namespace Tests\Feature;

use App\Domain\Cart\Services\CartPricingService;
use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PricingRuleResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The resolver caches the active ruleset — clear it so a stale entry
        // from a previous test never leaks into the next one.
        Cache::flush();

        // Pin the pricing config so assertions are deterministic regardless
        // of the environment's .env values.
        config([
            'pricing.safety_markup_percent' => 10,
            'pricing.min_margin_percent' => 1,
        ]);
    }

    private function makeVariant(float $costPrice = 100.0): ProductVariant
    {
        $category = Category::create(['name' => 'Gift Cards', 'slug' => 'gift-cards']);
        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Gaming',
            'slug' => 'gaming',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => 'zendit',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'Xbox (US)',
            'slug' => 'xbox-us-'.uniqid(),
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'offer-'.uniqid(),
            'currency' => 'USD',
            'face_value' => 10,
            'cost_price' => $costPrice,
            'retail_price' => $costPrice,
        ]);
    }

    public function test_global_markup_applies_when_no_specific_rule_exists(): void
    {
        PricingRule::create(['markup_type' => 'percentage', 'markup_value' => 8, 'is_active' => true]);

        $pricing = (new CartPricingService)->calculatePricing($this->makeVariant(100), 1);

        $this->assertEqualsWithDelta(8.0, (float) $pricing['markup_amount'], 0.001);
        $this->assertEqualsWithDelta(108.0, (float) $pricing['unit_price_snapshot'], 0.001);
    }

    public function test_category_rule_overrides_the_global_rule(): void
    {
        $variant = $this->makeVariant(100);
        PricingRule::create(['markup_type' => 'percentage', 'markup_value' => 8, 'is_active' => true]);
        PricingRule::create([
            'category_id' => $variant->product->category_id,
            'markup_type' => 'percentage',
            'markup_value' => 15,
            'is_active' => true,
        ]);

        $pricing = (new CartPricingService)->calculatePricing($variant, 1);

        $this->assertEqualsWithDelta(15.0, (float) $pricing['markup_amount'], 0.001);
    }

    public function test_product_rule_overrides_category_and_global(): void
    {
        $variant = $this->makeVariant(100);

        // Deliberately create the broad rules first: a correct resolver must
        // still pick the product override, not the first row that matches.
        PricingRule::create(['markup_type' => 'percentage', 'markup_value' => 8, 'is_active' => true]);
        PricingRule::create([
            'category_id' => $variant->product->category_id,
            'markup_type' => 'percentage',
            'markup_value' => 15,
            'is_active' => true,
        ]);
        PricingRule::create([
            'product_id' => $variant->product_id,
            'markup_type' => 'fixed',
            'markup_value' => 30,
            'is_active' => true,
        ]);

        $pricing = (new CartPricingService)->calculatePricing($variant, 1);

        // A fixed $30 markup on the product beats the 15% category and 8% global.
        $this->assertEqualsWithDelta(30.0, (float) $pricing['markup_amount'], 0.001);
        $this->assertEqualsWithDelta(130.0, (float) $pricing['unit_price_snapshot'], 0.001);
    }

    public function test_safety_markup_applies_when_no_rule_matches(): void
    {
        // No rules at all — the resolver must fall back to the configured
        // safety markup (10%), never sell at cost.
        $pricing = (new CartPricingService)->calculatePricing($this->makeVariant(100), 1);

        $this->assertEqualsWithDelta(10.0, (float) $pricing['markup_amount'], 0.001);
        $this->assertEqualsWithDelta(110.0, (float) $pricing['unit_price_snapshot'], 0.001);
    }

    public function test_margin_floor_prevents_pricing_at_or_below_cost(): void
    {
        $variant = $this->makeVariant(100);

        // A fixed markup of 0 would price exactly at cost — the 1% margin
        // floor must lift it to 101.
        PricingRule::create([
            'product_id' => $variant->product_id,
            'markup_type' => 'fixed',
            'markup_value' => 0,
            'is_active' => true,
        ]);

        $pricing = (new CartPricingService)->calculatePricing($variant, 1);

        $this->assertEqualsWithDelta(101.0, (float) $pricing['unit_price_snapshot'], 0.001);
        $this->assertEqualsWithDelta(1.0, (float) $pricing['markup_amount'], 0.001);
    }
}
