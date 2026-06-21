<?php

namespace Tests\Feature;

use App\Http\Controllers\EsimStoreController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * features.zendit_esims_enabled hides Zendit eSIMs storefront-wide without a
 * deploy. Airalo eSIMs always stay visible.
 */
class EsimSupplierFlagTest extends TestCase
{
    use RefreshDatabase;

    private function makeEsim(string $provider, string $country): Product
    {
        $category = Category::firstOrCreate(['slug' => 'esims'], ['name' => 'eSIMs', 'type' => 'digital']);
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'provider_name' => $provider,
            'country_code' => $country,
            'is_active' => true,
            'name' => ucfirst($provider).' '.$country.' eSIM',
            'slug' => $provider.'-'.strtolower($country).'-esim',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_available' => true,
            'currency' => 'USD',
            'cost_price' => 2,
            'retail_price' => 5,
            'face_value' => 5,
        ]);

        return $product;
    }

    private function slugsInSummary(): array
    {
        Cache::flush();

        return EsimStoreController::catalogSummary()->pluck('slug')->all();
    }

    public function test_zendit_esims_hidden_when_flag_off(): void
    {
        $this->makeEsim('airalo', 'FR');
        $this->makeEsim('zendit', 'NG');

        SiteSetting::put('features.zendit_esims_enabled', 'off', 'features');

        $slugs = $this->slugsInSummary();
        $this->assertContains('airalo-fr-esim', $slugs);
        $this->assertNotContains('zendit-ng-esim', $slugs);
    }

    public function test_zendit_esims_visible_when_flag_on(): void
    {
        $this->makeEsim('airalo', 'FR');
        $this->makeEsim('zendit', 'NG');

        SiteSetting::put('features.zendit_esims_enabled', 'on', 'features');

        $slugs = $this->slugsInSummary();
        $this->assertContains('airalo-fr-esim', $slugs);
        $this->assertContains('zendit-ng-esim', $slugs);
    }

    public function test_zendit_country_page_404s_when_flag_off(): void
    {
        $this->withoutVite();
        $this->makeEsim('zendit', 'NG');
        SiteSetting::put('features.zendit_esims_enabled', 'off', 'features');

        $this->get('/esims/country/NG')->assertNotFound();
    }
}
