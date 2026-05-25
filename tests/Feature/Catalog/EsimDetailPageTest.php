<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EsimDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function esimRegion(): Product
    {
        $category = Category::create(['name' => 'eSIMs', 'slug' => 'esims', 'type' => 'digital']);

        $product = Product::create([
            'category_id' => $category->id,
            'provider_name' => 'zendit',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'name' => 'United States Data eSIM',
            'slug' => 'united-states-data-esim',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'ESIM-US-7D-1GB',
            'currency' => 'USD',
            'face_value' => 2.03,
            'cost_price' => 1.50,
            'retail_price' => 2.03,
            'is_available' => true,
            'metadata' => [
                'plan_type' => 'data_only',
                'coverage' => ['US'],
                'raw_payload' => ['dataGB' => 1, 'durationDays' => 7, 'dataUnlimited' => false],
            ],
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'ESIM-US-30D-10GB',
            'currency' => 'USD',
            'face_value' => 12.14,
            'cost_price' => 9.00,
            'retail_price' => 12.14,
            'is_available' => true,
            'metadata' => [
                'plan_type' => 'data_only',
                'coverage' => ['US'],
                'raw_payload' => ['dataGB' => 10, 'durationDays' => 30, 'dataUnlimited' => false],
            ],
        ]);

        // Airalo-style voice + SMS plan: clean data_limit + reliable validity_days.
        ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'airalo_us-voice-5gb',
            'currency' => 'USD',
            'face_value' => 18.00,
            'cost_price' => 13.50,
            'retail_price' => 18.00,
            'is_available' => true,
            'metadata' => [
                'plan_type' => 'voice_sms_data',
                'supports_voice' => true,
                'supports_sms' => true,
                'data_limit' => '5 GB',
                'voice_limit' => '120 mins',
                'sms_limit' => '100 SMS',
                'validity_days' => 15,
                'countries' => ['US'],
            ],
        ]);

        return $product;
    }

    public function test_the_esim_store_page_renders_the_store_flow(): void
    {
        $this->withoutVite();

        $this->esimRegion();

        $this->get(route('shop.esim', 'united-states-data-esim'))
            ->assertOk()
            ->assertSee('Select country or region')
            ->assertSee('Select data plan')
            ->assertSee('Valid region:')
            ->assertSee('Estimated price')
            ->assertSee('Points you earn')
            ->assertSee('Add to cart')
            ->assertSee('Buy now');
    }

    public function test_the_esims_index_renders_the_store_with_a_default_region(): void
    {
        $this->withoutVite();

        $this->esimRegion();

        // The /esims entry point (what the nav links to) now resolves a default
        // region (US here) and renders the store directly — not a grid.
        $this->get(route('shop.esims'))
            ->assertOk()
            ->assertSee('Select country or region')
            ->assertSee('Select data plan')
            ->assertSee('United States')
            ->assertSee('Add to cart');
    }

    public function test_the_plan_cards_show_data_duration_and_tiers(): void
    {
        $this->withoutVite();

        $this->esimRegion();

        $this->get(route('shop.esim', 'united-states-data-esim'))
            ->assertOk()
            ->assertSee('1 GB')
            ->assertSee('10 GB')
            ->assertSee('7 Days')
            ->assertSee('30 Days')
            ->assertSee('Pin validity: 365 days')
            ->assertSee('Data only')
            ->assertSee('TRIP')
            ->assertSee('EXPLORER');
    }

    public function test_voice_and_sms_plans_show_their_extras(): void
    {
        $this->withoutVite();

        $this->esimRegion();

        // Airalo voice+SMS plans surface the phone-number badge and the minutes/SMS lines.
        $this->get(route('shop.esim', 'united-states-data-esim'))
            ->assertOk()
            ->assertSee('+Number')
            ->assertSee('120 mins')
            ->assertSee('100 SMS')
            ->assertSee('Voice, SMS &amp; data', false);
    }

    public function test_an_unknown_region_returns_404(): void
    {
        $this->get('/esims/this-region-does-not-exist')->assertNotFound();
    }
}
