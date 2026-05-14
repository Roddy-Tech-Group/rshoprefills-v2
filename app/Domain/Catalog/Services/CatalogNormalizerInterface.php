<?php

namespace App\Domain\Catalog\Services;

interface CatalogNormalizerInterface
{
    /**
     * Take a raw provider payload item, group it by brand/country,
     * and upsert into the products and product_variants tables.
     */
    public function normalizeAndSave(array $rawItem, string $providerName): void;
}
