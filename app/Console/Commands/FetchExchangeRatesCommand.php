<?php

namespace App\Console\Commands;

use App\Domain\Wallet\Jobs\SyncExchangeRatesJob;
use App\Domain\Wallet\Services\ExchangeRateFetcher;
use App\Models\CurrencyRate;
use App\Models\ProductVariant;
use Illuminate\Console\Command;

class FetchExchangeRatesCommand extends Command
{
    /**
     * Examples:
     *   php artisan rates:fetch                     # pull every fiat we know about + every fiat used by any product variant
     *   php artisan rates:fetch --codes=PKR,TZS     # pull just specific codes
     *   php artisan rates:fetch --activate          # also flip is_active=true on newly created rows
     */
    protected $signature = 'rates:fetch
        {--codes=* : Limit to specific ISO codes (omit to fetch every currency in use)}
        {--activate : Set is_active=true on currencies we auto-create}';

    protected $description = 'Pull live FX rates from the public provider, update currency_rates, then push into exchange_rates.';

    public function handle(ExchangeRateFetcher $fetcher): int
    {
        $this->info('Fetching live USD-base FX rates...');

        try {
            $live = $fetcher->fetchUsdRates();
        } catch (\Throwable $e) {
            $this->error('Fetch failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Provider returned %d currencies.', count($live)));

        // Build the target list. If --codes is passed, honour it exactly;
        // otherwise default to (a) every currency we already track plus
        // (b) every currency in use by any product variant. That auto-fills
        // exotic Zendit currencies (PKR, TZS, ZMW, EGP, BOB, UZS, ...) on
        // first run without manual seeding.
        $explicit = collect((array) $this->option('codes'))
            ->flatMap(fn ($c) => explode(',', $c))
            ->map(fn ($c) => strtoupper(trim($c)))
            ->filter()
            ->unique()
            ->values();

        if ($explicit->isNotEmpty()) {
            $targets = $explicit->all();
        } else {
            $tracked = CurrencyRate::pluck('code')->map(fn ($c) => strtoupper($c));
            $inUse = ProductVariant::distinct()
                ->whereNotNull('currency')
                ->pluck('currency')
                ->map(fn ($c) => strtoupper($c));
            $targets = $tracked->merge($inUse)->unique()->values()->all();
        }

        // Crypto codes - our public FX provider doesn't cover them. Leave the
        // existing admin-managed values intact.
        $crypto = ['BTC', 'ETH', 'SOL', 'USDT', 'USDC', 'BNB', 'LTC', 'BUSD'];

        $updated = 0;
        $created = 0;
        $skipped = [];

        foreach ($targets as $code) {
            $code = strtoupper($code);

            if (in_array($code, $crypto, true)) {
                continue;
            }
            if (! isset($live[$code])) {
                $skipped[] = $code;

                continue;
            }

            $rate = $live[$code];
            $existing = CurrencyRate::query()->where('code', $code)->first();

            if ($existing) {
                $existing->update(['rate_per_usd' => $rate]);
                $updated++;
            } else {
                CurrencyRate::create([
                    'code' => $code,
                    'name' => $code,
                    'type' => 'fiat',
                    'rate_per_usd' => $rate,
                    'sort_order' => 0,
                    'is_active' => (bool) $this->option('activate'),
                ]);
                $created++;
            }
        }

        $this->info(sprintf('%d currencies updated, %d created.', $updated, $created));

        if (! empty($skipped)) {
            $this->warn('Provider had no rate for: '.implode(', ', $skipped));
        }

        // Push the fresh CurrencyRate rows into exchange_rates so the runtime
        // CurrencyRateService serves them immediately.
        $this->info('Syncing into exchange_rates...');
        (new SyncExchangeRatesJob)->handle();

        $this->info('Done. Storefront prices should reflect the new rates on next request.');

        return self::SUCCESS;
    }
}
