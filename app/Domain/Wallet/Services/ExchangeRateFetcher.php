<?php

namespace App\Domain\Wallet\Services;

use Illuminate\Support\Facades\Http;

/**
 * Pulls a fresh USD-base rate snapshot from a free public FX provider so the
 * admin doesn't have to maintain ~160 currencies by hand. Crypto codes are
 * left alone - those are still admin-managed because public FX providers
 * don't cover them with the same quality.
 *
 * Provider: open.er-api.com (free, no API key, ~161 fiat currencies, ECB +
 * bank-feed sourced, daily updates). Swap to a different provider by editing
 * `fetchUsdRates()` - the rest of the system only cares that this method
 * returns a [code => rate_per_usd] array.
 */
class ExchangeRateFetcher
{
    private const ENDPOINT = 'https://open.er-api.com/v6/latest/USD';

    /**
     * Fetch the latest USD-base FX rates from the provider.
     *
     * @return array<string, float> keyed by uppercase ISO code, e.g. ['NGN' => 1492.5, ...]
     *
     * @throws \RuntimeException when the provider returns a non-success payload.
     */
    public function fetchUsdRates(): array
    {
        $response = Http::timeout(10)->retry(2, 500)->get(self::ENDPOINT);

        if (! $response->ok()) {
            throw new \RuntimeException('FX provider HTTP error: '.$response->status());
        }

        $body = $response->json();
        if (($body['result'] ?? null) !== 'success' || ! is_array($body['rates'] ?? null)) {
            throw new \RuntimeException('FX provider returned no rates payload.');
        }

        // Normalise keys to uppercase + values to float. Force USD to 1.0
        // even if the provider returns something else (some endpoints round).
        $rates = [];
        foreach ($body['rates'] as $code => $value) {
            $rates[strtoupper((string) $code)] = (float) $value;
        }
        $rates['USD'] = 1.0;

        return $rates;
    }
}
