<?php

namespace App\Domain\Catalog\Services;

use App\Jobs\MediaProcessorJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use Illuminate\Support\Str;

class ZenditNormalizer implements CatalogNormalizerInterface
{
    public function normalizeAndSave(array $rawItem, string $providerName): void
    {
        // 1. Ensure Gift Cards Category exists
        $category = Category::firstOrCreate(
            ['slug' => 'gift-cards'],
            ['name' => 'Gift Cards', 'type' => 'digital']
        );

        // 2. Normalize Subtype -> Subcategory
        $subtypeName = $rawItem['subtype'] ?? 'Uncategorized';
        $subcategorySlug = Str::slug($subtypeName);

        $subcategory = Subcategory::firstOrCreate(
            ['category_id' => $category->id, 'slug' => $subcategorySlug],
            ['name' => $subtypeName, 'is_featured' => false]
        );

        // 3. Determine Brand and Product Grouping
        $brandName = $rawItem['brand'] ?? 'Unknown Brand';
        $countryCode = strtoupper($rawItem['country'] ?? 'US');
        $currencyCode = strtoupper($rawItem['currency'] ?? 'USD');

        // Products are grouped by Brand + Country
        $productSlug = Str::slug("{$brandName}-{$countryCode}-{$providerName}");

        $product = Product::updateOrCreate(
            [
                'provider_name' => $providerName,
                // We use slug as the unique grouping key here, or we could use brand + country
                'slug' => $productSlug,
            ],
            [
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'provider_reference' => null, // Grouping object has no single reference
                'country_code' => $countryCode,
                'currency_code' => $currencyCode,
                'name' => "{$brandName} ({$countryCode})",
                'description' => $rawItem['description'] ?? "{$brandName} Gift Card for {$countryCode}",
                'redeem_instructions' => $rawItem['redeem_instructions'] ?? null,
                'terms_and_conditions' => $rawItem['terms'] ?? null,
                // Keep existing logo if we already processed it, otherwise take the raw one
                'logo_url' => Product::where('slug', $productSlug)->value('logo_url') ?? ($rawItem['logoUrl'] ?? null),
            ]
        );

        // 4. Create/Update Variant
        $offerId = $rawItem['offerId'];

        // Pricing logic: For Zendit, 'cost' is wholesale, 'faceValue' is retail (usually)
        // Note: minAmount and maxAmount dictate if it's variable.
        $costPrice = (float) ($rawItem['cost'] ?? 0);
        $faceValue = (float) ($rawItem['faceValue'] ?? $costPrice);
        $minAmount = isset($rawItem['minAmount']) ? (float) $rawItem['minAmount'] : null;
        $maxAmount = isset($rawItem['maxAmount']) ? (float) $rawItem['maxAmount'] : null;

        $isVariable = false;
        if ($minAmount !== null && $maxAmount !== null && $minAmount !== $maxAmount) {
            $isVariable = true;
        }

        ProductVariant::updateOrCreate(
            ['provider_offer_id' => $offerId],
            [
                'product_id' => $product->id,
                'sku' => $rawItem['sku'] ?? $offerId,
                'currency' => $currencyCode,
                'face_value' => $faceValue,
                'cost_price' => $costPrice,
                'retail_price' => $faceValue, // Default retail to face value for now
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
                'is_variable' => $isVariable,
                'is_available' => true, // Re-enable if it was disabled
                'metadata' => $rawItem, // Store entire raw snapshot for debugging/future-proofing
            ]
        );

        // Optional: Dispatch a job to download the logo if we haven't already and a URL exists.
        if (isset($rawItem['logoUrl']) && ! str_starts_with($product->logo_url, 'local/')) {
            MediaProcessorJob::dispatch($product->id, $rawItem['logoUrl']);
        }
    }
}
