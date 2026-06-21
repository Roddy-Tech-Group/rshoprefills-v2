<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A brand that was removed from the catalogue (its product deactivated) should
 * 301-redirect its old URL to the category listing rather than hard-404, so
 * Google drops it cleanly and visitors land on a live page. A slug that never
 * existed still returns a genuine 404.
 */
class RemovedBrandRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    private function makeGiftCardCategory(): Category
    {
        return Category::create(['name' => 'Gift Cards', 'slug' => 'gift-cards', 'type' => 'digital']);
    }

    public function test_removed_gift_card_brand_redirects_to_listing(): void
    {
        $category = $this->makeGiftCardCategory();

        // Brand still in the catalogue, but deactivated (removed).
        Product::create([
            'category_id' => $category->id,
            'provider_name' => 'zendit',
            'brand_key' => 'Acme',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'Acme Gift Card',
            'slug' => 'acme-gift-card',
            'is_active' => false,
        ]);

        $this->get('/gift-cards/acme')
            ->assertStatus(301)
            ->assertRedirect(route('shop.gift-cards'));
    }

    public function test_unknown_brand_slug_still_returns_404(): void
    {
        $this->makeGiftCardCategory();

        $this->get('/gift-cards/never-existed')->assertNotFound();
    }
}
