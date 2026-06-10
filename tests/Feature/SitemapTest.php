<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_html_sitemap_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.sitemap'))
            ->assertOk()
            ->assertSee('Find your way around')
            ->assertSee('Gift Cards')
            ->assertSee('Privacy Policy');
    }

    public function test_the_xml_sitemap_returns_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
        $response->assertSee('gift-cards', false);
    }

    public function test_the_xml_sitemap_covers_esim_store_pages(): void
    {
        $category = Category::create(['name' => 'eSIMs', 'slug' => 'esims', 'type' => 'digital']);

        $product = Product::create([
            'category_id' => $category->id,
            'provider_name' => 'zendit',
            'country_code' => 'FR',
            'currency_code' => 'USD',
            'name' => 'France Data eSIM',
            'slug' => 'esim-france-data',
            'is_active' => true,
        ]);

        ProductVariant::factory()->for($product)->create([
            'currency' => 'USD',
            'cost_price' => 4,
            'retail_price' => 6,
            'is_available' => true,
        ]);

        // The sitemap and the eSIM catalogue summary are both cached; clear so
        // this freshly-seeded product is reflected.
        Cache::flush();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('esims/esim-france-data', false);
    }
}
