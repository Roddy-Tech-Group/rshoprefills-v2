<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Services\AiraloEsimNormalizer;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiraloEsimNormalizerTest extends TestCase
{
    use RefreshDatabase;

    private function sync(): void
    {
        // One operator that CLAIMS voice at the operator level (the old false-flag
        // trigger), but only one of its packages actually carries minutes.
        $rawItem = [
            'country_code' => 'US',
            'title' => 'United States',
            'operators' => [[
                'title' => 'TestNet',
                'plan_type' => 'data-voice-text',
                'coverages' => [[
                    'name' => 'United States',
                    'networks' => [
                        ['name' => 'T-Mobile', 'types' => ['5G']],
                        ['name' => 'Verizon', 'types' => [['name' => '5G']]],
                    ],
                ]],
                'packages' => [
                    ['id' => 'us-1gb-7d', 'data' => '1 GB', 'day' => 7, 'price' => 9, 'net_price' => 3, 'voice' => null, 'text' => null],
                    ['id' => 'us-5gb-voice', 'data' => '5 GB', 'day' => 30, 'price' => 50, 'net_price' => 36, 'voice' => 50, 'text' => 50],
                ],
            ]],
        ];

        (new AiraloEsimNormalizer)->normalizeAndSave($rawItem, 'airalo');
    }

    public function test_data_only_package_is_not_flagged_as_voice(): void
    {
        $this->sync();

        $variant = ProductVariant::where('provider_offer_id', 'airalo_us-1gb-7d')->firstOrFail();

        $this->assertFalse($variant->metadata['supports_voice']);
        $this->assertFalse($variant->metadata['supports_sms']);
        $this->assertNull($variant->metadata['voice_limit']);   // no "Included" placeholder
        $this->assertNull($variant->metadata['sms_limit']);
        $this->assertSame('data_only', $variant->metadata['plan_type']);
    }

    public function test_real_voice_package_captures_the_actual_minutes(): void
    {
        $this->sync();

        $variant = ProductVariant::where('provider_offer_id', 'airalo_us-5gb-voice')->firstOrFail();

        $this->assertTrue($variant->metadata['supports_voice']);
        $this->assertTrue($variant->metadata['supports_sms']);
        $this->assertSame('50', $variant->metadata['voice_limit']);
        $this->assertSame('50', $variant->metadata['sms_limit']);
        $this->assertSame('voice_sms_data', $variant->metadata['plan_type']);
    }

    public function test_carrier_networks_are_captured_from_operator_coverages(): void
    {
        $this->sync();

        $variant = ProductVariant::where('provider_offer_id', 'airalo_us-1gb-7d')->firstOrFail();
        $detail = $variant->metadata['networks_detail'];

        $names = array_column($detail, 'name');
        $this->assertContains('T-Mobile', $names);
        $this->assertContains('Verizon', $names);
        // Speed parsed from both ['5G'] (string) and [['name'=>'5G']] (object) shapes.
        foreach ($detail as $net) {
            $this->assertSame('5G', $net['speed']);
        }
    }

    public function test_pricing_uses_net_price_as_cost_and_retail_as_face(): void
    {
        $this->sync();

        $variant = ProductVariant::where('provider_offer_id', 'airalo_us-5gb-voice')->firstOrFail();

        $this->assertEqualsWithDelta(36.0, (float) $variant->cost_price, 0.001);
        $this->assertEqualsWithDelta(50.0, (float) $variant->face_value, 0.001);
    }
}
