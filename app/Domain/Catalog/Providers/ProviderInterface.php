<?php

namespace App\Domain\Catalog\Providers;

interface ProviderInterface
{
    /**
     * Get the unique internal identifier for this provider (e.g., 'zendit', 'reloadly').
     */
    public function getProviderName(): string;

    /**
     * Fetch a paginated list of the provider's catalog.
     *
     * @param  int  $page  The page number to fetch.
     * @param  int  $limit  The number of items per page.
     * @return array Raw provider payload containing items and metadata.
     */
    public function fetchCatalog(int $page = 1, int $limit = 100): array;

    /**
     * Fetch a specific offer/product detail from the provider.
     *
     * @param  string  $providerReference  The provider's unique ID for the product/offer.
     * @return array Raw provider payload for the specific item.
     */
    public function fetchOfferDetails(string $providerReference): array;
}
