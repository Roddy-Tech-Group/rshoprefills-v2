<?php

namespace Tests\Feature\Catalog;

use App\Http\Controllers\EsimStoreController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The "Discover Global" worldwide eSIM is surfaced on the home page + dashboard.
 * EsimStoreController::discoverGlobal() is the shared data source: it returns the
 * global product and its priced plans, or null when it isn't in the catalogue.
 */
class DiscoverGlobalTest extends TestCase
{
    use RefreshDatabase;

    private function makeGlobalEsim(): Product
    {
        $category = Category::create(['name' => 'eSIMs', 'slug' => 'esims', 'type' => 'digital']);

        $product = Product::create([
            'category_id' => $category->id,
            'provider_name' => 'airalo',
            'country_code' => 'WW',
            'currency_code' => 'USD',
            'name' => 'Global Data eSIM',
            'slug' => 'esim-ww-discover-global',
            'is_active' => true,
        ]);

        ProductVariant::factory()->for($product)->create([
            'currency' => 'USD',
            'face_value' => 5,
            'retail_price' => 5,
            'is_available' => true,
            'is_variable' => false,
            'metadata' => ['data_limit' => '1 GB', 'duration_days' => 7],
        ]);

        return $product;
    }

    public function test_discover_global_returns_product_with_priced_plans(): void
    {
        $this->makeGlobalEsim();

        $dg = EsimStoreController::discoverGlobal();

        $this->assertNotNull($dg);
        $this->assertSame('esim-ww-discover-global', $dg['product']->slug);
        $this->assertCount(1, $dg['plans']);
        $this->assertSame('1 GB', $dg['plans'][0]['data']);
        $this->assertSame(7, $dg['plans'][0]['days']);
        $this->assertGreaterThan(0, $dg['plans'][0]['price']);
    }

    public function test_discover_global_returns_null_when_absent(): void
    {
        Category::create(['name' => 'eSIMs', 'slug' => 'esims', 'type' => 'digital']);

        $this->assertNull(EsimStoreController::discoverGlobal());
    }

    public function test_home_page_renders_discover_global_section(): void
    {
        $this->withoutVite();
        $this->makeGlobalEsim();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Discover Global eSIM');
    }

    /**
     * The dashboard renders this inside an off-center column, so it must pass
     * :contained="true" to drop the carousel's 100vw full-bleed track (w-screen)
     * - otherwise the track overflows onto the right rail. The home page keeps it.
     */
    public function test_contained_carousel_drops_the_full_bleed_track(): void
    {
        $this->makeGlobalEsim();

        $contained = Blade::render('<x-home.discover-global :contained="true" />');
        $this->assertStringNotContainsString('w-screen', $contained);

        $bleed = Blade::render('<x-home.discover-global />');
        $this->assertStringContainsString('w-screen', $bleed);
    }
}
