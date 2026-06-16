<?php

namespace Tests\Feature;

use App\Domain\Cart\Services\CartPricingService;
use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Provider-scoped pricing rules. Suppliers price the same category very
 * differently (Airalo eSIMs vs Zendit eSIMs), so a rule may target one
 * supplier within a scope. Resolution contract:
 *
 *   - within a tier, a rule matching the product's supplier beats the
 *     provider-agnostic rule;
 *   - a provider-scoped rule NEVER applies to another supplier's products;
 *   - tier order is unchanged: product > subcategory > category > global.
 */
class PricingRuleProviderScopeTest extends TestCase
{
    use RefreshDatabase;

    private Category $esims;

    private Subcategory $dataEsims;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pricing.safety_markup_percent' => 10.0, 'pricing.min_margin_percent' => 1.0]);

        $this->esims = Category::create(['name' => 'eSIMs', 'slug' => 'esims', 'type' => 'digital']);
        $this->dataEsims = Subcategory::create(['category_id' => $this->esims->id, 'name' => 'Data eSIMs', 'slug' => 'data-esim']);
    }

    private function makeProduct(string $provider, ?int $subcategoryId = null): Product
    {
        return Product::create([
            'category_id' => $this->esims->id,
            'subcategory_id' => $subcategoryId,
            'provider_name' => $provider,
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => ucfirst($provider).' eSIM '.uniqid(),
            'slug' => $provider.'-esim-'.uniqid(),
        ]);
    }

    private function price(Product $product, float $base = 100.0): float
    {
        Cache::forget('pricing_rules.active');

        // Fresh service instance: rules are memoised per instance.
        return (new CartPricingService)->resolveRetailPrice($product, $base);
    }

    public function test_each_supplier_gets_its_own_category_rule_and_others_get_the_generic_one(): void
    {
        PricingRule::create(['category_id' => $this->esims->id, 'provider_name' => 'airalo', 'markup_type' => 'percentage', 'markup_value' => 5, 'is_active' => true]);
        PricingRule::create(['category_id' => $this->esims->id, 'provider_name' => 'zendit', 'markup_type' => 'percentage', 'markup_value' => 12, 'is_active' => true]);
        PricingRule::create(['category_id' => $this->esims->id, 'markup_type' => 'percentage', 'markup_value' => 20, 'is_active' => true]);

        $this->assertEqualsWithDelta(105.0, $this->price($this->makeProduct('airalo')), 0.0001);
        $this->assertEqualsWithDelta(112.0, $this->price($this->makeProduct('zendit')), 0.0001);
        // A third supplier matches neither scoped rule - generic eSIMs rule applies.
        $this->assertEqualsWithDelta(120.0, $this->price($this->makeProduct('othertel')), 0.0001);
    }

    public function test_provider_rule_never_leaks_to_other_suppliers_even_without_a_generic_rule(): void
    {
        PricingRule::create(['category_id' => $this->esims->id, 'provider_name' => 'airalo', 'markup_type' => 'percentage', 'markup_value' => 5, 'is_active' => true]);

        // Zendit has no matching rule at any tier: the 10% safety markup fires,
        // NOT Airalo's 5%.
        $this->assertEqualsWithDelta(110.0, $this->price($this->makeProduct('zendit')), 0.0001);
    }

    public function test_product_rule_still_beats_a_provider_scoped_category_rule(): void
    {
        $airalo = $this->makeProduct('airalo');

        PricingRule::create(['category_id' => $this->esims->id, 'provider_name' => 'airalo', 'markup_type' => 'percentage', 'markup_value' => 5, 'is_active' => true]);
        PricingRule::create(['product_id' => $airalo->id, 'markup_type' => 'percentage', 'markup_value' => 30, 'is_active' => true]);

        $this->assertEqualsWithDelta(130.0, $this->price($airalo), 0.0001);
    }

    public function test_provider_scoped_subcategory_rule_beats_the_generic_subcategory_rule(): void
    {
        PricingRule::create(['category_id' => $this->esims->id, 'subcategory_id' => $this->dataEsims->id, 'provider_name' => 'airalo', 'markup_type' => 'percentage', 'markup_value' => 4, 'is_active' => true]);
        PricingRule::create(['category_id' => $this->esims->id, 'subcategory_id' => $this->dataEsims->id, 'markup_type' => 'percentage', 'markup_value' => 8, 'is_active' => true]);

        $this->assertEqualsWithDelta(104.0, $this->price($this->makeProduct('airalo', $this->dataEsims->id)), 0.0001);
        $this->assertEqualsWithDelta(108.0, $this->price($this->makeProduct('zendit', $this->dataEsims->id)), 0.0001);
    }

    public function test_provider_match_is_case_insensitive(): void
    {
        PricingRule::create(['category_id' => $this->esims->id, 'provider_name' => 'airalo', 'markup_type' => 'percentage', 'markup_value' => 5, 'is_active' => true]);

        $this->assertEqualsWithDelta(105.0, $this->price($this->makeProduct('Airalo')), 0.0001);
    }

    public function test_inactive_provider_rule_is_ignored(): void
    {
        PricingRule::create(['category_id' => $this->esims->id, 'provider_name' => 'airalo', 'markup_type' => 'percentage', 'markup_value' => 5, 'is_active' => false]);
        PricingRule::create(['category_id' => $this->esims->id, 'markup_type' => 'percentage', 'markup_value' => 20, 'is_active' => true]);

        $this->assertEqualsWithDelta(120.0, $this->price($this->makeProduct('airalo')), 0.0001);
    }
}
