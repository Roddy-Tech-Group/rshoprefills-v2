<?php

namespace App\Domain\Catalog\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenditEsimProvider implements ProviderInterface
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
        // Zendit eSIMs live at /esim/offers (NOT /catalog/offers with a type filter), paginated by
        // _offset / _limit. See https://developers.zendit.io/api -> GET /v1/esim/offers.
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/esim/offers", [
                '_limit' => $limit,
                '_offset' => max(0, ($page - 1) * $limit),
            ]);

        if ($response->failed()) {
            Log::error('Zendit eSIM Catalog Fetch Failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('Failed to fetch Zendit eSIM catalog.');
        }

        return $response->json();
    }

    public function fetchOfferDetails(string $providerReference): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/esim/offers/{$providerReference}");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to fetch Zendit eSIM offer: {$providerReference}");
        }

        // Zendit returns the offer object directly (no "data" wrapper).
        return $response->json();
    }
}
