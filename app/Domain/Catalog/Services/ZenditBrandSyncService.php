<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Providers\ZenditProvider;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * Hydrates Product rows with brand-level assets and country-level redemption
 * details from Zendit's separate /brands/* API.
 *
 * Why this exists: the /vouchers/offers payload that CatalogSyncService uses
 * carries no logos, hero images, redemption instructions, or terms. Those
 * live behind:
 *   - GET /v1/brands/{brand}                          (brand-wide assets)
 *   - GET /v1/brands/{brand}/redemptionInstructions   (per-country redemption)
 *
 * We make at most one /brands/{brand} call per unique brand, then one
 * /redemptionInstructions call per (brand, country) pair.
 */
class ZenditBrandSyncService
{
    public function __construct(
        private readonly ZenditProvider $provider
    ) {}

    /**
     * Run a full brand sync against every Zendit product currently in the DB.
     *
     * @return array{brands_synced: int, redemptions_synced: int, brands_failed: int}
     */
    public function sync(): array
    {
        $brandsSynced = 0;
        $redemptionsSynced = 0;
        $brandsFailed = 0;

        // Pass 1: brand-wide assets (logo / hero / brand color / description).
        // One API call per unique brand_key, applied to every Product row that shares it.
        $uniqueBrands = Product::query()
            ->where('provider_name', 'zendit')
            ->whereNotNull('brand_key')
            ->distinct()
            ->pluck('brand_key')
            ->filter()
            ->values();

        Log::info('ZenditBrandSync: fetching brand assets', ['unique_brands' => $uniqueBrands->count()]);

        // Brand -> redemptionInstructions index: a per-country list of links that
        // carry the deliveryType + language the redemption endpoint requires.
        // Captured from the single brand fetch and reused by Pass 2.
        $redemptionIndex = [];

        foreach ($uniqueBrands as $brandKey) {
            $brand = $this->provider->fetchBrand($brandKey);

            if (empty($brand)) {
                $brandsFailed++;

                continue;
            }

            Product::where('provider_name', 'zendit')
                ->where('brand_key', $brandKey)
                ->update(array_filter([
                    'logo_url' => $brand['brandLogo'] ?? null,
                    'featured_image' => $brand['brandBigImage'] ?? ($brand['brandGiftImage'] ?? null),
                    'brand_color' => $this->normaliseColor($brand['brandColor'] ?? null),
                    'description' => $brand['description'] ?? null,
                ], fn ($v) => $v !== null && $v !== ''));

            $redemptionIndex[$brandKey] = is_array($brand['redemptionInstructions'] ?? null)
                ? $brand['redemptionInstructions']
                : [];

            $brandsSynced++;
        }

        // Pass 2: per-country redemption instructions + terms + video.
        // One API call per unique (brand_key, country_code) pair, written to the matching Product.
        $brandCountryPairs = Product::query()
            ->where('provider_name', 'zendit')
            ->whereNotNull('brand_key')
            ->whereNotNull('country_code')
            ->select('brand_key', 'country_code')
            ->distinct()
            ->get();

        Log::info('ZenditBrandSync: fetching redemption instructions', ['pairs' => $brandCountryPairs->count()]);

        foreach ($brandCountryPairs as $pair) {
            // The redemption endpoint needs the deliveryType + language from the
            // brand's index entry for this country (an uppercase-country-only
            // call returns []).
            $entry = collect($redemptionIndex[$pair->brand_key] ?? [])
                ->first(fn ($e) => is_array($e) && strcasecmp((string) ($e['country'] ?? ''), (string) $pair->country_code) === 0);

            if (! $entry) {
                continue;
            }

            $redemption = $this->provider->fetchRedemptionInstructions(
                $pair->brand_key,
                $pair->country_code,
                $entry['deliveryType'] ?? null,
                $entry['language'] ?? 'en',
            );

            if (empty($redemption)) {
                continue;
            }

            $updateData = array_filter([
                'redeem_instructions' => $redemption['redemptionInstructions'] ?? null,
                'terms_and_conditions' => $redemption['terms'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            // Stash the video URL + delivery type in metadata so the detail page can pick them up
            // without us needing another column.
            if (! empty($redemption['redemptionVideo']) || ! empty($redemption['deliveryType'])) {
                $product = Product::where('provider_name', 'zendit')
                    ->where('brand_key', $pair->brand_key)
                    ->where('country_code', $pair->country_code)
                    ->first();

                if ($product) {
                    $metadata = is_array($product->metadata) ? $product->metadata : [];
                    if (! empty($redemption['redemptionVideo'])) {
                        $metadata['redemption_video'] = $redemption['redemptionVideo'];
                    }
                    if (! empty($redemption['deliveryType'])) {
                        $metadata['delivery_type'] = $redemption['deliveryType'];
                    }
                    if (! empty($redemption['language'])) {
                        $metadata['redemption_language'] = $redemption['language'];
                    }
                    $updateData['metadata'] = $metadata;
                }
            }

            if (! empty($updateData)) {
                Product::where('provider_name', 'zendit')
                    ->where('brand_key', $pair->brand_key)
                    ->where('country_code', $pair->country_code)
                    ->update($updateData);

                $redemptionsSynced++;
            }
        }

        return [
            'brands_synced' => $brandsSynced,
            'redemptions_synced' => $redemptionsSynced,
            'brands_failed' => $brandsFailed,
        ];
    }

    /**
     * Zendit returns brand colours as either "#107C10" or "107C10" depending on the brand.
     * Normalise to the leading-# form so the Blade view can shove it straight into a CSS
     * variable without a runtime check.
     */
    private function normaliseColor(?string $color): ?string
    {
        if (! $color) {
            return null;
        }

        $color = trim($color);
        if ($color === '') {
            return null;
        }

        return str_starts_with($color, '#') ? $color : "#{$color}";
    }
}
