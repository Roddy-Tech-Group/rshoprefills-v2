<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Providers\ZenditProvider;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

class SyncTopupLogosCommand extends Command
{
    protected $signature = 'topups:sync-logos';

    protected $description = 'Fetch operator logos + brand colours for mobile top-up products from the Zendit /brands API';

    public function handle(): int
    {
        $category = Category::where('slug', 'mobile-airtime')->first();

        if (! $category) {
            $this->warn('No mobile-airtime category yet. Run topups:sync first.');

            return self::SUCCESS;
        }

        // The /topups/offers payload carries no logo, so — like gift cards — we
        // resolve brand assets from Zendit's separate /brands/{brand} endpoint.
        // One call per unique operator brand_key, applied to every country row.
        $brandKeys = Product::where('category_id', $category->id)
            ->where('provider_name', 'zendit')
            ->whereNotNull('brand_key')
            ->distinct()
            ->pluck('brand_key')
            ->filter()
            ->values();

        if ($brandKeys->isEmpty()) {
            $this->warn('No mobile top-up operators found. Run topups:sync first.');

            return self::SUCCESS;
        }

        $provider = new ZenditProvider;
        $synced = 0;
        $missing = 0;

        $this->info("Fetching logos for {$brandKeys->count()} operators from Zendit /brands...");
        $bar = $this->output->createProgressBar($brandKeys->count());
        $bar->start();

        foreach ($brandKeys as $brandKey) {
            $brand = $provider->fetchBrand($brandKey);
            $logo = $brand['brandLogo'] ?? null;
            $color = $brand['brandColor'] ?? null;

            if (! $logo) {
                $missing++;
                $bar->advance();

                continue;
            }

            Product::where('category_id', $category->id)
                ->where('brand_key', $brandKey)
                ->update(array_filter([
                    'logo_url' => $logo,
                    'brand_color' => $this->normaliseColor($color),
                ], fn ($v) => $v !== null && $v !== ''));

            $synced++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. {$synced} operators got a logo; {$missing} had none in Zendit /brands.");

        if ($missing > 0 && $synced === 0) {
            $this->warn('Zendit /brands returned nothing for any operator — operators are likely not in that catalog. A different logo source is needed.');
        }

        return self::SUCCESS;
    }

    private function normaliseColor(?string $color): ?string
    {
        if (! $color || trim($color) === '') {
            return null;
        }

        $color = trim($color);

        return str_starts_with($color, '#') ? $color : "#{$color}";
    }
}
