<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Subcategory $subcategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Gift Cards', 'slug' => 'gift-cards', 'type' => 'digital']);
        $this->subcategory = Subcategory::create(['category_id' => $this->category->id, 'name' => 'Gaming', 'slug' => 'gaming']);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $this->category->id,
            'subcategory_id' => $this->subcategory->id,
            'provider_name' => 'zendit',
            'brand_key' => 'Steam',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'Steam Gift Card',
            'slug' => 'steam-gift-card',
            'is_active' => true,
        ], $overrides));
    }

    public function test_brand_search_returns_active_matches(): void
    {
        $this->product();

        $response = $this->getJson(route('api.search.brands', ['q' => 'steam']))->assertOk();

        $this->assertGreaterThanOrEqual(1, count($response->json()));
        $this->assertNotEmpty($response->json('0.name'));
        $this->assertNotEmpty($response->json('0.slug'));
    }

    public function test_brand_search_requires_at_least_two_characters(): void
    {
        $this->product();

        $this->getJson(route('api.search.brands', ['q' => 's']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_brand_search_excludes_inactive_products(): void
    {
        $this->product([
            'brand_key' => 'HiddenBrand',
            'name' => 'Hidden Brand Card',
            'slug' => 'hidden-brand-card',
            'is_active' => false,
        ]);

        $this->getJson(route('api.search.brands', ['q' => 'hidden']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_an_unknown_esim_slug_returns_404(): void
    {
        $this->get('/esims/this-region-does-not-exist')->assertNotFound();
    }
}
