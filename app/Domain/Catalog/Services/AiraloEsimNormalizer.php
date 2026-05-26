<?php

namespace App\Domain\Catalog\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use Illuminate\Support\Str;

class AiraloEsimNormalizer implements CatalogNormalizerInterface
{
    public function normalizeAndSave(array $rawItem, string $providerName): void
    {
        // 1. Ensure eSIMs Category exists
        $category = Category::firstOrCreate(
            ['slug' => 'esims'],
            ['name' => 'eSIMs', 'type' => 'digital']
        );

        // 2. Map to Data eSIM Subcategory (fallback)
        $subcategory = Subcategory::firstOrCreate(
            ['category_id' => $category->id, 'slug' => 'data-esim'],
            ['name' => 'Data eSIMs', 'is_featured' => false]
        );

        // $rawItem is a Country/Region object from Airalo which contains operators -> packages
        $rawLocation = strtoupper($rawItem['country_code'] ?? 'WW');
        $countryCode = strlen($rawLocation) === 2 ? $rawLocation : 'WW';
        $regionName = $rawItem['title'] ?? ($countryCode === 'WW' ? 'Global' : $countryCode);

        // Grouping logic: identical to Zendit so Airalo plans fall under the same Region Product
        $productSlug = Str::slug("esim-{$countryCode}-{$regionName}");
        $productName = $countryCode === 'WW' ? 'Global eSIM' : "{$regionName} eSIM";

        $product = Product::updateOrCreate(
            [
                'provider_name' => $providerName,
                'slug' => $productSlug,
            ],
            [
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'country_code' => $countryCode,
                'currency_code' => 'USD', // Airalo defaults to USD
                'name' => $productName,
                // Update description generically to accommodate both data and voice
                'description' => "High-speed eSIM for {$regionName}. Scan QR code to activate.",
                'redeem_instructions' => 'Go to Settings > Cellular > Add Cellular Plan and scan the QR Code.',
                'logo_url' => $rawItem['image']['url'] ?? null,
            ]
        );

        // 4. Variant Normalization
        $operators = $rawItem['operators'] ?? [];

        foreach ($operators as $operator) {
            $network = $operator['title'] ?? 'Multiple';
            $packages = $operator['packages'] ?? [];

            // Real carrier networks for this operator (Airalo exposes them per coverage
            // as { name, types: [...] }, e.g. T-Mobile 5G / Verizon 5G). Deduped by name.
            $networksByName = [];
            foreach (($operator['coverages'] ?? []) as $coverage) {
                foreach (($coverage['networks'] ?? []) as $net) {
                    if (! is_array($net) || empty($net['name'])) {
                        continue;
                    }
                    $speed = null;
                    foreach ((array) ($net['types'] ?? []) as $type) {
                        $s = is_array($type) ? ($type['name'] ?? null) : $type;
                        if ($s) {
                            $speed = $s;
                        }
                    }
                    $networksByName[$net['name']] = $speed;
                }
            }
            $networksDetail = [];
            foreach ($networksByName as $name => $speed) {
                $networksDetail[] = ['name' => $name, 'speed' => $speed];
            }

            foreach ($packages as $pkg) {
                $offerId = $pkg['id'] ?? null;
                if (! $offerId) {
                    continue;
                }

                $costPrice = (float) ($pkg['net_price'] ?? $pkg['price'] ?? 0);
                $faceValue = (float) ($pkg['price'] ?? $costPrice);

                // Voice/SMS come ONLY from the package's real numeric allowance. The
                // operator-level plan_type is unreliable (it tags data-only packages
                // as voice). No "Included" placeholder — show only what the data says.
                $voiceVal = $pkg['voice'] ?? null;
                $smsVal = $pkg['text'] ?? null;
                $supportsVoice = is_numeric($voiceVal) && (float) $voiceVal > 0;
                $supportsSms = is_numeric($smsVal) && (float) $smsVal > 0;
                $supportsData = ! empty($pkg['data']) || ! empty($pkg['is_unlimited']);

                $planType = ($supportsVoice || $supportsSms) ? 'voice_sms_data' : 'data_only';

                $metadata = [
                    'provider' => 'airalo',
                    'provider_package_id' => $offerId,
                    'plan_type' => $planType,
                    'supports_data' => $supportsData,
                    'supports_voice' => $supportsVoice,
                    'supports_sms' => $supportsSms,
                    'data_limit' => $pkg['data'] ?? null,
                    'voice_limit' => $supportsVoice ? (string) $voiceVal : null,
                    'sms_limit' => $supportsSms ? (string) $smsVal : null,
                    'validity_days' => (int) ($pkg['day'] ?? 0),
                    'countries' => [$countryCode], // Could be an array for regional
                    'network' => $network,
                    'networks_detail' => $networksDetail,
                    'activation_policy' => $operator['activation_policy'] ?? 'automatic',
                    'is_rechargeable' => (bool) ($operator['rechargeability'] ?? false),
                    'raw_payload' => $pkg,
                ];

                ProductVariant::updateOrCreate(
                    ['provider_offer_id' => "airalo_{$offerId}"], // Namespace to avoid ID collisions
                    [
                        'product_id' => $product->id,
                        'subcategory_id' => $subcategory->id,
                        'sku' => "AIRALO-{$offerId}",
                        'currency' => 'USD',
                        'face_value' => $faceValue,
                        'cost_price' => $costPrice,
                        'retail_price' => $faceValue,
                        'is_variable' => false,
                        'is_available' => true,
                        'metadata' => $metadata,
                    ]
                );
            }
        }
    }
}
