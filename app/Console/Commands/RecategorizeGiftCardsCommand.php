<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;

/**
 * Gift-card categories come straight from Zendit's per-offer `subTypes` array,
 * which is noisy — a single stray offer can carry a wrong subType and, because
 * the storefront filters a subcategory by "any variant in it", that drags the
 * whole brand into the wrong subcategory (the classic "Xbox under Dating").
 *
 * A gift-card brand belongs in ONE category. This command resolves each brand's
 * single dominant subcategory (a majority vote across all its variants) and
 * pins every product + variant of that brand to it. Run after each catalog sync.
 *
 * Scoped to the `gift-cards` category only — top-ups / bill-payments keep their
 * intentional variant-level subcategories.
 */
class RecategorizeGiftCardsCommand extends Command
{
    protected $signature = 'catalog:recategorize-giftcards';

    protected $description = 'Pin every gift-card brand to its single dominant subcategory so a stray Zendit subType cannot leak a brand into the wrong category.';

    public function handle(): int
    {
        $category = Category::where('slug', 'gift-cards')->first();

        if (! $category) {
            $this->warn('No gift-cards category found — nothing to do.');

            return self::SUCCESS;
        }

        $brands = Product::where('category_id', $category->id)
            ->whereNotNull('brand_key')
            ->get(['id', 'brand_key', 'subcategory_id'])
            ->groupBy('brand_key');

        $this->info("Recategorizing {$brands->count()} gift-card brands...");

        $brandsChanged = 0;
        $productsUpdated = 0;
        $variantsUpdated = 0;

        foreach ($brands as $brandProducts) {
            $productIds = $brandProducts->pluck('id');

            // Majority vote: the most common subcategory across every variant of
            // every product for this brand. Falls back to the products' own
            // subcategory when no variant carries one.
            $dominant = ProductVariant::whereIn('product_id', $productIds)
                ->whereNotNull('subcategory_id')
                ->selectRaw('subcategory_id, COUNT(*) as votes')
                ->groupBy('subcategory_id')
                ->orderByDesc('votes')
                ->value('subcategory_id')
                ?? $brandProducts->pluck('subcategory_id')->filter()->first();

            if (! $dominant) {
                continue;
            }

            $p = Product::whereIn('id', $productIds)
                ->where(fn ($q) => $q->where('subcategory_id', '!=', $dominant)->orWhereNull('subcategory_id'))
                ->update(['subcategory_id' => $dominant]);

            $v = ProductVariant::whereIn('product_id', $productIds)
                ->where(fn ($q) => $q->where('subcategory_id', '!=', $dominant)->orWhereNull('subcategory_id'))
                ->update(['subcategory_id' => $dominant]);

            $productsUpdated += $p;
            $variantsUpdated += $v;

            if ($p > 0 || $v > 0) {
                $brandsChanged++;
            }
        }

        $this->info("Done. {$brandsChanged} brands corrected — {$productsUpdated} products and {$variantsUpdated} variants reassigned.");

        return self::SUCCESS;
    }
}
