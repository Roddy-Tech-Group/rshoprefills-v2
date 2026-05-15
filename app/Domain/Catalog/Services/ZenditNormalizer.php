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

        // 2. Normalize Subtype -> Subcategory.
        // Zendit returns subTypes as an array; pick the first one (or fall back).
        $subTypes = $rawItem['subTypes'] ?? [];
        $subtypeName = is_array($subTypes) && ! empty($subTypes) ? $subTypes[0] : ($rawItem['subtype'] ?? 'Uncategorized');
        $subcategorySlug = Str::slug($subtypeName);

        $subcategory = Subcategory::firstOrCreate(
            ['category_id' => $category->id, 'slug' => $subcategorySlug],
            ['name' => $subtypeName, 'is_featured' => false]
        );

        // 3. Determine Brand and Product Grouping.
        // Zendit exposes both a short machine key (brand) and a human label (brandName).
        $brandKey = $rawItem['brand'] ?? 'unknown';
        $brandLabel = $rawItem['brandName'] ?? $brandKey;
        $countryCode = strtoupper($rawItem['country'] ?? 'US');

        // Currency lives on the nested send/price objects (denominated in cents via currencyDivisor).
        $priceBlock = $rawItem['price'] ?? [];
        $sendBlock = $rawItem['send'] ?? [];
        $costBlock = $rawItem['cost'] ?? [];
        $currencyCode = strtoupper($priceBlock['currency'] ?? $sendBlock['currency'] ?? $rawItem['currency'] ?? 'USD');

        // Products are grouped by Brand + Country
        $productSlug = Str::slug("{$brandKey}-{$countryCode}-{$providerName}");

        // Preserve any fields that were hydrated by ZenditBrandSyncService (logo, hero,
        // brand_color, description, redeem_instructions, terms_and_conditions). The
        // /vouchers/offers payload doesn't include those — only /brands/* does — so
        // we mustn't blow them away on a routine catalog re-sync.
        $existing = Product::where('slug', $productSlug)->first();

        $product = Product::updateOrCreate(
            [
                'provider_name' => $providerName,
                'slug' => $productSlug,
            ],
            [
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'provider_reference' => null,
                'brand_key' => $brandKey,
                'country_code' => $countryCode,
                'currency_code' => $currencyCode,
                'name' => "{$brandLabel} ({$countryCode})",
                'description' => $existing?->description
                    ?: ($rawItem['shortNotes'] ?? $rawItem['notes'] ?? $rawItem['description'] ?? "{$brandLabel} Gift Card for {$countryCode}"),
                'redeem_instructions' => $existing?->redeem_instructions ?: ($rawItem['redeem_instructions'] ?? null),
                'terms_and_conditions' => $existing?->terms_and_conditions ?: ($rawItem['terms'] ?? null),
                'logo_url' => $existing?->logo_url ?: ($rawItem['logoUrl'] ?? null),
                'featured_image' => $existing?->featured_image,
                'brand_color' => $existing?->brand_color,
            ]
        );

        // 4. Create/Update Variant. Zendit money fields are integer minor units; divide by currencyDivisor.
        $offerId = $rawItem['offerId'];
        $priceType = $rawItem['priceType'] ?? 'FIXED';

        $priceDiv = (float) ($priceBlock['currencyDivisor'] ?? 100);
        $sendDiv = (float) ($sendBlock['currencyDivisor'] ?? 100);
        $costDiv = (float) ($costBlock['currencyDivisor'] ?? 100);

        $faceValue = isset($priceBlock['fixed']) ? ((float) $priceBlock['fixed']) / $priceDiv
                     : (isset($sendBlock['fixed']) ? ((float) $sendBlock['fixed']) / $sendDiv : null);
        $costPrice = isset($costBlock['fixed']) ? ((float) $costBlock['fixed']) / $costDiv : ($faceValue ?? 0.0);
        $retailPrice = $faceValue ?? $costPrice;

        // VARIABLE-priced cards expose min/max on the send block.
        $minAmount = isset($sendBlock['min']) ? ((float) $sendBlock['min']) / $sendDiv : null;
        $maxAmount = isset($sendBlock['max']) ? ((float) $sendBlock['max']) / $sendDiv : null;

        $isVariable = $priceType === 'VARIABLE'
            || ($minAmount !== null && $maxAmount !== null && $minAmount !== $maxAmount);

        ProductVariant::updateOrCreate(
            ['provider_offer_id' => $offerId],
            [
                'product_id' => $product->id,
                'sku' => $rawItem['sku'] ?? $offerId,
                'currency' => $currencyCode,
                'face_value' => $faceValue,
                'cost_price' => $costPrice,
                'retail_price' => $retailPrice,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
                'is_variable' => $isVariable,
                'is_available' => (bool) ($rawItem['enabled'] ?? true),
                'metadata' => $rawItem,
            ]
        );

        // Optional logo processing (Zendit offers list doesn't return one, but keep the hook for future).
        if (! empty($rawItem['logoUrl']) && ! str_starts_with((string) $product->logo_url, 'local/')) {
            MediaProcessorJob::dispatch($product->id, $rawItem['logoUrl']);
        }
    }
}
