<?php

namespace App\Domain\Catalog\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenditProvider implements ProviderInterface
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.zendit.api_key', '');

        $explicitUrl = config('services.zendit.base_url');
        if ($explicitUrl) {
            $this->baseUrl = $explicitUrl;
        } elseif (str_starts_with($this->apiKey, 'sand_')) {
            $this->baseUrl = 'https://api.sandbox.zendit.io/v1';
        } else {
            $this->baseUrl = 'https://api.zendit.io/v1';
        }
    }

    public function getProviderName(): string
    {
        return 'zendit';
    }

    public function fetchCatalog(int $page = 1, int $limit = 100): array
    {
        // Zendit paginates by _offset / _limit (NOT page / limit), and gift cards are scoped via the
        // /vouchers/offers endpoint (not a generic /catalog/offers with a type filter).
        // Reference: https://developers.zendit.io/api  -> GET /v1/vouchers/offers
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/vouchers/offers", [
                '_limit' => $limit,
                '_offset' => max(0, ($page - 1) * $limit),
            ]);

        if ($response->failed()) {
            Log::error('Zendit Catalog Fetch Failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('Failed to fetch Zendit catalog.');
        }

        return $response->json();
    }

    public function fetchOfferDetails(string $providerReference): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/vouchers/offers/{$providerReference}");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to fetch Zendit offer: {$providerReference}");
        }

        // Zendit returns the offer object directly (no "data" wrapper).
        return $response->json();
    }

    /**
     * Fetch a brand's marketing/media assets from Zendit.
     *
     * Returns: brand, brandName, brandLogo, brandLogoExtension, brandBigImage,
     * brandGiftImage, brandColor, brandInfoPdf, description, inputMasks,
     * redemptionInstructions, requiredFieldsLabels.
     *
     * Reference: GET /v1/brands/{brand}
     */
    public function fetchBrand(string $brandKey): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->connectTimeout(30)
            ->timeout(60)
            ->retry(3, 500, throw: false)
            ->get("{$this->baseUrl}/brands/{$brandKey}");

        if ($response->failed()) {
            Log::warning('Zendit Brand Fetch Failed', [
                'brand' => $brandKey,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch a brand's redemption instructions + terms for a specific country.
     *
     * Returns: country, deliveryType, language, redemptionInstructions, terms,
     * redemptionVideo.
     *
     * Reference: GET /v1/brands/{brand}/redemptionInstructions
     */
    public function fetchRedemptionInstructions(string $brandKey, ?string $countryCode = null, ?string $deliveryType = null, string $language = 'en'): array
    {
        // Zendit's redemption endpoint returns [] unless it gets the exact combo
        // the brand's redemptionInstructions index advertises: a LOWERCASE
        // country plus the matching deliveryType + language. (An uppercase
        // country with no deliveryType/language - the old call - always 200s [].)
        $query = ['language' => $language];
        if ($countryCode) {
            $query['country'] = strtolower($countryCode);
        }
        if ($deliveryType) {
            $query['deliveryType'] = $deliveryType;
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->connectTimeout(30)
            ->timeout(60)
            ->retry(3, 500, throw: false)
            ->get("{$this->baseUrl}/brands/{$brandKey}/redemptionInstructions", $query);

        if ($response->failed()) {
            Log::warning('Zendit Redemption Instructions Fetch Failed', [
                'brand' => $brandKey,
                'country' => $countryCode,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [];
        }

        return $response->json() ?? [];
    }
}
