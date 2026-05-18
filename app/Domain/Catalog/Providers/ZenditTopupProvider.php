<?php

namespace App\Domain\Catalog\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenditTopupProvider implements ProviderInterface
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.zendit.api_key', env('ZENDIT_API_KEY', ''));
        $this->baseUrl = config('services.zendit.base_url', env('ZENDIT_BASE_URL', 'https://api.zendit.io/v1'));
    }

    public function getProviderName(): string
    {
        return 'zendit';
    }

    public function fetchCatalog(int $page = 1, int $limit = 100): array
    {
        // Zendit mobile top-ups live at /topups/offers (NOT /catalog/offers with a type
        // filter), paginated by _offset / _limit like vouchers and eSIMs.
        // Reference: https://developers.zendit.io/api -> GET /v1/topups/offers
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/topups/offers", [
                '_limit' => $limit,
                '_offset' => max(0, ($page - 1) * $limit),
            ]);

        if ($response->failed()) {
            Log::error('Zendit Top-up Catalog Fetch Failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('Failed to fetch Zendit top-up catalog.');
        }

        return $response->json();
    }

    public function fetchOfferDetails(string $providerReference): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/topups/offers/{$providerReference}");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to fetch Zendit top-up offer: {$providerReference}");
        }

        // Zendit returns the offer object directly (no "data" wrapper).
        return $response->json();
    }
}
