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
        $this->apiKey = config('services.zendit.api_key', env('ZENDIT_API_KEY', ''));
        $this->baseUrl = config('services.zendit.base_url', env('ZENDIT_BASE_URL', 'https://api.zendit.io/v1'));
    }

    public function getProviderName(): string
    {
        return 'zendit';
    }

    public function fetchCatalog(int $page = 1, int $limit = 100): array
    {
        // Add query parameter to filter specifically for gift cards as per this phase's requirement.
        // If Zendit provides a 'type' or 'subtype' filter, it should be used here.
        // For now, we assume standard pagination.
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/catalog/offers", [
                'page' => $page,
                'limit' => $limit,
                'type' => 'gift_card', // Example: restricting fetch to gift cards
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
            ->get("{$this->baseUrl}/catalog/offers/{$providerReference}");

        if ($response->failed()) {
            throw new \RuntimeException("Failed to fetch Zendit offer: {$providerReference}");
        }

        return $response->json('data'); // Assuming data wraps the object
    }
}
