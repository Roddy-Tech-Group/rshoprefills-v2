<?php

namespace App\Domain\Wallet\Services;

use App\Domain\Wallet\Exceptions\StaleRateException;
use App\Models\CurrencyRate;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    /**
     * Resolve the active exchange rate between two currencies.
     * Uses the exchange_rates table first, then falls back to the currency_rates table.
     *
     * @throws StaleRateException
     */
    public function resolveRate(string $baseCurrency, string $targetCurrency): float
    {
        $base = strtoupper(trim($baseCurrency));
        $target = strtoupper(trim($targetCurrency));

        if ($base === $target) {
            return 1.0;
        }

        // 1. Attempt to resolve from exchange_rates table
        $rateRow = ExchangeRate::active()
            ->where('base_currency', $base)
            ->where('target_currency', $target)
            ->orderBy('fetched_at', 'desc')
            ->first();

        if ($rateRow) {
            $this->validateRateFreshness($rateRow);

            return (float) $rateRow->rate;
        }

        // 2. Try the inverse rate
        $inverseRateRow = ExchangeRate::active()
            ->where('base_currency', $target)
            ->where('target_currency', $base)
            ->orderBy('fetched_at', 'desc')
            ->first();

        if ($inverseRateRow) {
            $this->validateRateFreshness($inverseRateRow);
            $rate = (float) $inverseRateRow->rate;

            return $rate > 0 ? (1.0 / $rate) : 0.0;
        }

        // 3. Fallback: Resolve using the currency_rates table
        return $this->resolveFallbackRate($base, $target);
    }

    /**
     * Convert an amount between two currencies.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rate = $this->resolveRate($from, $to);

        return round($amount * $rate, 4);
    }

    /**
     * Generate an audit snapshot array for a currency pair.
     */
    public function generateSnapshot(string $base, string $target): array
    {
        $base = strtoupper(trim($base));
        $target = strtoupper(trim($target));

        $rateRow = ExchangeRate::active()
            ->where('base_currency', $base)
            ->where('target_currency', $target)
            ->orderBy('fetched_at', 'desc')
            ->first();

        if ($rateRow) {
            return [
                'base' => $base,
                'target' => $target,
                'rate' => (float) $rateRow->rate,
                'provider' => $rateRow->provider,
                'source' => $rateRow->source,
                'fetched_at' => $rateRow->fetched_at->toIso8601String(),
                'type' => 'registry',
            ];
        }

        // Fallback
        $rate = $this->resolveFallbackRate($base, $target);

        return [
            'base' => $base,
            'target' => $target,
            'rate' => $rate,
            'provider' => 'currency_rates_fallback',
            'source' => 'system_fallback',
            'fetched_at' => now()->toIso8601String(),
            'type' => 'fallback',
        ];
    }

    /**
     * Validate rate freshness and raise alerts if stale.
     *
     * @throws StaleRateException
     */
    protected function validateRateFreshness(ExchangeRate $rateRow): void
    {
        $ageHours = $rateRow->fetched_at->diffInHours(now());

        // Stale rate threshold: 24 hours
        if ($ageHours > 24) {
            Log::warning('Stale exchange rate detected in registry.', [
                'id' => $rateRow->id,
                'base' => $rateRow->base_currency,
                'target' => $rateRow->target_currency,
                'fetched_at' => $rateRow->fetched_at,
                'age_hours' => $ageHours,
            ]);

            if ($ageHours > 48) {
                throw new StaleRateException("Exchange rate is critically stale. Age: {$ageHours} hours. Base: {$rateRow->base_currency}, Target: {$rateRow->target_currency}");
            }
        }
    }

    /**
     * Resolves a synthetic rate using the legacy currency_rates table.
     */
    protected function resolveFallbackRate(string $base, string $target): float
    {
        $baseRateObj = CurrencyRate::resolve($base);
        $targetRateObj = CurrencyRate::resolve($target);

        $basePerUsd = (float) $baseRateObj->rate_per_usd;
        $targetPerUsd = (float) $targetRateObj->rate_per_usd;

        if ($basePerUsd <= 0 || $targetPerUsd <= 0) {
            return 0.0;
        }

        // If converting USD -> NGN: targetPerUsd / basePerUsd (USD is base, NGN is target)
        // targetPerUsd (1400 NGN) / basePerUsd (1.0 USD) = 1400 NGN per USD
        return $targetPerUsd / $basePerUsd;
    }
}
