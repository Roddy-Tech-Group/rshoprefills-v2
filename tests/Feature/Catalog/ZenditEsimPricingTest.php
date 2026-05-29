<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Services\ZenditEsimNormalizer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZenditEsimPricingTest extends TestCase
{
    use RefreshDatabase;

    /** A Zendit offer prices cost/price as { fixed, currencyDivisor } objects. */
    private function zenditOffer(): array
    {
        return [
            'offerId' => 'ESIM-AD-15D-2GB-NOROAM',
            'country' => 'AD',
            'cost' => ['fixed' => 1241, 'currencyDivisor' => 100, 'currency' => 'USD'],
            'price' => ['fixed' => 1700, 'currencyDivisor' => 100, 'suggestedFixed' => 1700, 'currency' => 'USD'],
            'dataGB' => 2,
            'durationDays' => 15,
            'brand' => 'eSIM',
        ];
    }

    public function test_the_normalizer_divides_fixed_by_currency_divisor(): void
    {
        (new ZenditEsimNormalizer)->normalizeAndSave($this->zenditOffer(), 'zendit');

        $variant = ProductVariant::where('provider_offer_id', 'ESIM-AD-15D-2GB-NOROAM')->firstOrFail();

        // 1241/100 = 12.41 cost, 1700/100 = 17.00 retail — not the old $1 placeholder.
        $this->assertEqualsWithDelta(12.41, (float) $variant->cost_price, 0.001);
        $this->assertEqualsWithDelta(17.00, (float) $variant->face_value, 0.001);
        $this->assertEqualsWithDelta(17.00, (float) $variant->retail_price, 0.001);
    }

    public function test_the_backfill_command_reprices_existing_dollar_one_variants(): void
    {
        $category = Category::create(['name' => 'eSIMs', 'slug' => 'esims', 'type' => 'digital']);
        $product = Product::create([
            'category_id' => $category->id,
            'provider_name' => 'zendit',
            'country_code' => 'AD',
            'currency_code' => 'USD',
            'name' => 'AD Data eSIM',
            'slug' => 'esim-ad-ad',
            'is_active' => true,
        ]);

        // Mispriced legacy row: real values only in raw_payload.
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'ESIM-AD-15D-2GB-NOROAM',
            'currency' => 'USD',
            'cost_price' => 1.00,
            'face_value' => 1.00,
            'retail_price' => 1.00,
            'is_available' => true,
            'metadata' => [
                'raw_payload' => [
                    'cost' => ['fixed' => 1241, 'currencyDivisor' => 100],
                    'price' => ['fixed' => 1700, 'currencyDivisor' => 100],
                ],
            ],
        ]);

        $this->artisan('esims:reprice-zendit')->assertSuccessful();

        $variant->refresh();
        $this->assertEqualsWithDelta(12.41, (float) $variant->cost_price, 0.001);
        $this->assertEqualsWithDelta(17.00, (float) $variant->face_value, 0.001);
        $this->assertEqualsWithDelta(17.00, (float) $variant->retail_price, 0.001);
    }

    public function test_the_backfill_skips_variants_without_object_prices(): void
    {
        $category = Category::create(['name' => 'eSIMs', 'slug' => 'esims', 'type' => 'digital']);
        $product = Product::create([
            'category_id' => $category->id,
            'provider_name' => 'airalo',
            'country_code' => 'AD',
            'currency_code' => 'USD',
            'name' => 'AD eSIM',
            'slug' => 'esim-ad-airalo',
            'is_active' => true,
        ]);

        // Airalo-style row: scalar price in raw_payload, real columns already set.
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'provider_offer_id' => 'airalo_ad-1gb',
            'currency' => 'USD',
            'cost_price' => 3.50,
            'face_value' => 5.00,
            'retail_price' => 5.00,
            'is_available' => true,
            'metadata' => ['raw_payload' => ['price' => '5.00', 'data' => '1 GB']],
        ]);

        $this->artisan('esims:reprice-zendit')->assertSuccessful();

        $variant->refresh();
        $this->assertEqualsWithDelta(5.00, (float) $variant->face_value, 0.001);
    }
}
