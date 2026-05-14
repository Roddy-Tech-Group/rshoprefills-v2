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
        $this->apiKey = config('services.zendit.api_key', env('ZENDIT_API_KEY', ''));
        $this->baseUrl = config('services.zendit.base_url', env('ZENDIT_BASE_URL', 'https://api.zendit.io/v1'));
    }

    public function getProviderName(): string
    {
        return 'zendit';
    }

    public function fetchCatalog(int $page = 1, int $limit = 100): array
    {
        // Explicitly filter Zendit catalog for eSIMs
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/catalog/offers", [
                'page' => $page,
                'limit' => $limit,
                'type' => 'esim',
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
            ->get("{$this->baseUrl}/catalog/offers/{$providerReference}");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to fetch Zendit eSIM offer: {$providerReference}");
        }

        return $response->json('data');
    }
}
