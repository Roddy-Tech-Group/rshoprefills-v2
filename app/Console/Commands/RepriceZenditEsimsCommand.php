<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use Illuminate\Console\Command;

/**
 * Recomputes Zendit eSIM variant prices from their stored raw_payload. Older syncs
 * cast Zendit's `cost`/`price` objects straight to float (yielding 1.0), so every
 * eSIM was flat-priced at ~$1. The real amount is `fixed / currencyDivisor`.
 */
class RepriceZenditEsimsCommand extends Command
{
    protected $signature = 'esims:reprice-zendit {--dry-run : Show what would change without writing}';

    protected $description = 'Recompute Zendit eSIM variant prices from their stored raw_payload (fixes the $1 placeholder bug)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        ProductVariant::query()
            ->whereHas('product.category', fn ($q) => $q->where('slug', 'esims'))
            ->chunkById(200, function ($variants) use (&$updated, &$skipped, $dryRun) {
                foreach ($variants as $variant) {
                    $raw = $variant->metadata['raw_payload'] ?? [];
                    $cost = is_array($raw['cost'] ?? null) ? $raw['cost'] : [];
                    $price = is_array($raw['price'] ?? null) ? $raw['price'] : [];

                    // Only Zendit offers carry these object price fields; Airalo packages
                    // store a scalar price, so they're left untouched.
                    if (! isset($price['fixed']) && ! isset($cost['fixed'])) {
                        $skipped++;

                        continue;
                    }

                    $costDivisor = (($cost['currencyDivisor'] ?? 100) ?: 100);
                    $priceDivisor = (($price['currencyDivisor'] ?? 100) ?: 100);

                    $costPrice = isset($cost['fixed'])
                        ? round((float) $cost['fixed'] / $costDivisor, 4)
                        : (float) $variant->cost_price;
                    $faceValue = isset($price['fixed'])
                        ? round((float) $price['fixed'] / $priceDivisor, 4)
                        : $costPrice;

                    if (! $dryRun) {
                        $variant->forceFill([
                            'cost_price' => $costPrice,
                            'face_value' => $faceValue,
                            'retail_price' => $faceValue,
                        ])->save();
                    }

                    $updated++;
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."Repriced {$updated} Zendit eSIM variants ({$skipped} skipped).");

        if (! $dryRun && $updated > 0) {
            cache()->forget('esim-catalog-summary-v5');
            $this->line('Cleared esim-catalog-summary cache.');
        }

        return self::SUCCESS;
    }
}
