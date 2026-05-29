<?php

namespace App\Jobs;

use App\Domain\Catalog\Providers\ZenditProvider;
use App\Domain\Catalog\Services\ZenditBrandSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Walks every distinct brand in the synced Zendit catalog and pulls its
 * logo / hero / brand colour / redemption details via the /brands/* endpoints.
 *
 * Dispatch order matters: run /catalog:sync (SyncZenditGiftCardsJob) first to
 * populate Products, THEN this job to hydrate them with brand assets.
 */
class SyncZenditBrandsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800; // 30 minutes; brand catalogue can be hundreds of brands.

    public function handle(): void
    {
        $service = new ZenditBrandSyncService(new ZenditProvider);

        Log::info('SyncZenditBrandsJob: starting');

        try {
            $result = $service->sync();

            Log::info('SyncZenditBrandsJob: completed', $result);
        } catch (\Throwable $e) {
            Log::error('SyncZenditBrandsJob: failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
