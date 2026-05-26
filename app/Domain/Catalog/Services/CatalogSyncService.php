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

        // Providers return different shapes:
        //   Zendit: { "total": int, "limit": int, "offset": int, "list": [ ...items... ] }
        //   Airalo: { "data": [ ...items... ], "links": {...}, "meta": { "current_page", "last_page", "total" } }
        $items = $rawPayload['list'] ?? $rawPayload['data'] ?? [];
        $meta = $rawPayload['meta'] ?? [];
        $total = $rawPayload['total'] ?? ($meta['total'] ?? ($meta['totalCount'] ?? 0));

        $processedItems = 0;

        foreach ($items as $rawItem) {
            $this->normalizer->normalizeAndSave($rawItem, $this->provider->getProviderName());
            $processedItems++;
        }

        // Prefer explicit page metadata (Airalo), then a "next" link, then fall back
        // to offset math against the total (Zendit).
        if (isset($meta['current_page'], $meta['last_page'])) {
            $hasMore = (int) $meta['current_page'] < (int) $meta['last_page'];
        } elseif (! empty($rawPayload['links']['next'])) {
            $hasMore = true;
        } else {
            $hasMore = $total > 0 && ($page * $limit) < $total;
        }

        return [
            'processed' => $processedItems,
            'has_more' => $hasMore,
            'total' => $total,
        ];
    }
}
