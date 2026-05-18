<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Providers\ZenditTopupProvider;
use App\Domain\Catalog\Services\CatalogSyncService;
use App\Domain\Catalog\Services\ZenditTopupNormalizer;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

class SyncTopupsCommand extends Command
{
    protected $signature = 'topups:sync {--page=1 : Page number to start syncing from}';

    protected $description = 'Sync the Zendit mobile top-up catalog synchronously, page by page, then report the operators pulled';

    public function handle(): int
    {
        $syncService = new CatalogSyncService(new ZenditTopupProvider, new ZenditTopupNormalizer);

        $page = max(1, (int) $this->option('page'));
        $totalProcessed = 0;

        $this->info('Syncing Zendit mobile top-ups...');

        do {
            try {
                $result = $syncService->sync($page, 100);
            } catch (\Throwable $e) {
                $this->error("Page {$page} failed: {$e->getMessage()}");

                return self::FAILURE;
            }

            $totalProcessed += $result['processed'];
            $this->line("  page {$page}: {$result['processed']} offers  (running total {$totalProcessed} of {$result['total']})");
            $page++;
        } while ($result['has_more']);

        $this->info("Done. Processed {$totalProcessed} offers.");

        // Summary — operators are Products grouped under the mobile-airtime category.
        $category = Category::where('slug', 'mobile-airtime')->first();
        $operators = $category
            ? Product::where('category_id', $category->id)
                ->withCount('variants')
                ->orderBy('country_code')
                ->orderBy('name')
                ->get()
            : collect();

        $this->newLine();
        $this->info("Mobile top-up operators: {$operators->count()}");

        if ($operators->isNotEmpty()) {
            $this->table(
                ['Country', 'Operator', 'Amounts'],
                $operators->map(fn (Product $p) => [$p->country_code, $p->name, $p->variants_count])
            );
        }

        return self::SUCCESS;
    }
}
