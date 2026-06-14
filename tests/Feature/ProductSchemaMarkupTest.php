<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductSchemaMarkupTest extends TestCase
{
    use RefreshDatabase;

    private function giftCardBrand(): Product
    {
        $category = Category::create(['name' => 'Gift Cards', 'slug' => 'gift-cards', 'type' => 'digital']);

        // "Acme" has no display-name alias, so the schema name stays deterministic.
        $product = Product::create([
            'category_id' => $category->id,
            'provider_name' => 'zendit',
            'brand_key' => 'Acme',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'Acme Gift Card',
            'slug' => 'acme-gift-card',
            'is_active' => true,
        ]);

        ProductVariant::factory()->for($product)->create([
            'currency' => 'USD',
            'face_value' => 10,
            'retail_price' => 10,
            'is_available' => true,
            'is_variable' => false,
        ]);
        ProductVariant::factory()->for($product)->create([
            'currency' => 'USD',
            'face_value' => 50,
            'retail_price' => 50,
            'is_available' => true,
            'is_variable' => false,
        ]);

        return $product;
    }

    public function test_gift_card_page_emits_product_schema_with_aggregate_offer(): void
    {
        $this->withoutVite();
        $this->giftCardBrand();

        $response = $this->get(route('shop.brand', ['brandSlug' => 'acme']))->assertOk();

        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('Acme Gift Card', false);
        $response->assertSee('"@type":"AggregateOffer"', false);
        // The offer should span the cheapest -> priciest denomination.
        $response->assertSee('"lowPrice":"10"', false);
        $response->assertSee('"highPrice":"50"', false);
        $response->assertSee('"priceCurrency":"USD"', false);
        $response->assertSee('https://schema.org/InStock', false);
    }

    public function test_gift_card_page_emits_aggregate_rating_and_reviews_from_published_reviews(): void
    {
        $this->withoutVite();
        $this->giftCardBrand();

        Review::create([
            'initials' => 'JD',
            'author_name' => 'Jane Doe',
            'body' => 'Fast delivery and great prices, very happy.',
            'rating' => 5,
            'source' => 'RshopRefills',
            'reviewed_at' => now(),
            'is_published' => true,
        ]);

        // The schema review aggregate is cached; clear it so the new review counts.
        Cache::flush();

        $response = $this->get(route('shop.brand', ['brandSlug' => 'acme']))->assertOk();

        $response->assertSee('"@type":"AggregateRating"', false);
        $response->assertSee('"ratingValue":"5"', false);
        $response->assertSee('"reviewCount":"1"', false);
        $response->assertSee('"@type":"Review"', false);
        $response->assertSee('Jane Doe', false);
    }

    public function test_gift_card_page_omits_review_schema_when_no_published_reviews(): void
    {
        $this->withoutVite();
        $this->giftCardBrand();

        Cache::flush();

        $response = $this->get(route('shop.brand', ['brandSlug' => 'acme']))->assertOk();

        $response->assertDontSee('"@type":"AggregateRating"', false);
    }

    public function test_gift_card_page_emits_breadcrumb_schema(): void
    {
        $this->withoutVite();
        $this->giftCardBrand();

        $response = $this->get(route('shop.brand', ['brandSlug' => 'acme']))->assertOk();

        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('Gift Cards', false);
    }
}
