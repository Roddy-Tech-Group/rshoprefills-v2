<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Providers\ProviderInterface;

/**
 * High-level service to orchestrate catalog syncing from a provider.
 */
class CatalogSyncService
{
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly CatalogNormalizerInterface $normalizer
    ) {}

    /**
     * Fetch raw data from the provider and normalize it into our domain models.
     */
    public function sync(int $page = 1, int $limit = 100): array
    {
        $rawPayload = $this->provider->fetchCatalog($page, $limit);

        // Zendit payload typically has a "data" array for items and a "meta" object for pagination
        $items = $rawPayload['data'] ?? [];
        $meta = $rawPayload['meta'] ?? ['totalCount' => 0, 'page' => 1];

        $processedItems = 0;

        foreach ($items as $rawItem) {
            $this->normalizer->normalizeAndSave($rawItem, $this->provider->getProviderName());
            $processedItems++;
        }

        return [
            'processed' => $processedItems,
            'has_more' => isset($meta['totalCount']) && ($page * $limit) < $meta['totalCount'],
            'total' => $meta['totalCount'] ?? 0,
        ];
    }
}
