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

            foreach ($packages as $pkg) {
                $offerId = $pkg['id'] ?? null;
                if (!$offerId) {
                    continue;
                }

                $costPrice = (float) ($pkg['net_price'] ?? $pkg['price'] ?? 0);
                $faceValue = (float) ($pkg['price'] ?? $costPrice);

                $planTypeStr = strtolower($operator['plan_type'] ?? 'data');
                $supportsVoice = str_contains($planTypeStr, 'voice') || !empty($pkg['voice']);
                $supportsSms = str_contains($planTypeStr, 'text') || str_contains($planTypeStr, 'sms') || !empty($pkg['text']);
                $supportsData = str_contains($planTypeStr, 'data') || !empty($pkg['data']);

                $planType = 'data_only';
                if ($supportsVoice || $supportsSms) {
                    $planType = 'voice_sms_data';
                }

                $metadata = [
                    'provider' => 'airalo',
                    'provider_package_id' => $offerId,
                    'plan_type' => $planType,
                    'supports_data' => $supportsData,
                    'supports_voice' => $supportsVoice,
                    'supports_sms' => $supportsSms,
                    'data_limit' => $pkg['data'] ?? 'Unknown',
                    'voice_limit' => $pkg['voice'] ?? ($supportsVoice ? 'Included' : null),
                    'sms_limit' => $pkg['text'] ?? ($supportsSms ? 'Included' : null),
                    'validity_days' => (int) ($pkg['day'] ?? 0),
                    'countries' => [$countryCode], // Could be an array for regional
                    'network' => $network,
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
