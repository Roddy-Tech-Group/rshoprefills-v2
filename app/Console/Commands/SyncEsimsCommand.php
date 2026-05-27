<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Providers\ZenditEsimProvider;
use App\Domain\Catalog\Services\CatalogSyncService;
use App\Domain\Catalog\Services\ZenditEsimNormalizer;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

class SyncEsimsCommand extends Command
{
    protected $signature = 'esims:sync {--page=1 : Page number to start syncing from}';

    protected $description = 'Sync the Zendit eSIM catalog synchronously, page by page, then report the regions pulled';

    public function handle(): int
    {
        $syncService = new CatalogSyncService(new ZenditEsimProvider, new ZenditEsimNormalizer);

        $page = max(1, (int) $this->option('page'));
        $totalProcessed = 0;

        $this->info('Syncing Zendit eSIMs...');

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

        $category = Category::where('slug', 'esims')->first();
        $regions = $category
            ? Product::where('category_id', $category->id)
                ->withCount('variants')
                ->orderBy('country_code')
                ->orderBy('name')
                ->get()
            : collect();

        $this->newLine();
        $this->info("eSIM Regions/Types: {$regions->count()}");

        if ($regions->isNotEmpty()) {
            $this->table(
                ['Country/Region', 'Name', 'Plans'],
                $regions->map(fn (Product $p) => [$p->country_code, $p->name, $p->variants_count])
            );
        }

        return self::SUCCESS;
    }
}
