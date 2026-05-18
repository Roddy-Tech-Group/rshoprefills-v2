<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Models\Subcategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillVariantSubcategoriesCommand extends Command
{
    protected $signature = 'catalog:backfill-variant-subcategories';

    protected $description = 'Populate product_variants.subcategory_id for rows synced before the column existed';

    public function handle(): int
    {
        // [category_id => [subcategory slug => id]] for fast in-memory resolution.
        $byCategory = Subcategory::get(['id', 'category_id', 'slug'])
            ->groupBy('category_id')
            ->map(fn ($group) => $group->pluck('id', 'slug'));

        $updated = 0;
        $fromFallback = 0;

        $this->info('Backfilling variant subcategories...');

        ProductVariant::with('product:id,category_id,subcategory_id')
            ->chunkById(500, function ($variants) use ($byCategory, &$updated, &$fromFallback) {
                foreach ($variants as $variant) {
                    $product = $variant->product;
                    if (! $product) {
                        continue;
                    }

                    $resolved = null;

                    // Prefer the variant's own subtype from the raw provider payload.
                    $subTypes = $variant->metadata['subTypes'] ?? [];
                    $subtypeName = is_array($subTypes) && ! empty($subTypes) ? $subTypes[0] : null;

                    if ($subtypeName) {
                        $resolved = $byCategory[$product->category_id][Str::slug($subtypeName)] ?? null;
                    }

                    // Fall back to the product's own subcategory so every row is set
                    // (covers eSIM variants, whose metadata has no top-level subTypes).
                    if (! $resolved) {
                        $resolved = $product->subcategory_id;
                        $fromFallback++;
                    }

                    if ($resolved && $variant->subcategory_id !== $resolved) {
                        $variant->subcategory_id = $resolved;
                        $variant->save();
                        $updated++;
                    }
                }
            });

        $this->info("Done. Updated {$updated} variants ({$fromFallback} via product-subcategory fallback).");

        return self::SUCCESS;
    }
}
