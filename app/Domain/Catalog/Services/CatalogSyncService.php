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

        // Zendit's catalog endpoints (/vouchers/offers, /esim/offers, /topups/offers) return:
        //   { "total": int, "limit": int, "offset": int, "list": [ ...items... ] }
        // Fall back to legacy "data" / "meta" keys so this works if any provider returns the older shape.
        $items = $rawPayload['list'] ?? $rawPayload['data'] ?? [];
        $total = $rawPayload['total'] ?? ($rawPayload['meta']['totalCount'] ?? 0);

        $processedItems = 0;

        foreach ($items as $rawItem) {
            $this->normalizer->normalizeAndSave($rawItem, $this->provider->getProviderName());
            $processedItems++;
        }

        return [
            'processed' => $processedItems,
            'has_more' => $total > 0 && ($page * $limit) < $total,
            'total' => $total,
        ];
    }
}
