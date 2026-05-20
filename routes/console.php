<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

\Illuminate\Support\Facades\Schedule::job(new \App\Domain\Wallet\Jobs\SyncExchangeRatesJob)->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Domain\Wallet\Jobs\ReconcilePendingFundingsJob)->hourly();

// Sweep PaymentSessions past their 15-minute TTL and cancel the linked Order /
// WalletFunding, so half-finished checkout attempts don't sit Pending on the
// customer's dashboard forever. Five-minute cadence — the dead state shows for
// at most ~5 min between cron ticks.
\Illuminate\Support\Facades\Schedule::job(new \App\Domain\Payment\Jobs\ExpireStalePaymentSessionsJob)->everyFiveMinutes();

// Same job, on demand. Useful after a debugging session left dozens of Pending
// orders, or any time you want to flush stale sessions without waiting for cron.
Artisan::command('payments:expire-stale', function () {
    $this->info('Sweeping stale payment sessions...');
    dispatch_sync(new \App\Domain\Payment\Jobs\ExpireStalePaymentSessionsJob);
    $this->info('Done.');
})->purpose('Expire payment sessions past their TTL and cancel their orders/fundings');

Artisan::command('zendit:sync-giftcards', function () {
    $this->info('Setting default queue connection to sync...');
    config(['queue.default' => 'sync']);

    $this->info('Dispatching SyncZenditGiftCardsJob synchronously...');
    dispatch(new \App\Jobs\SyncZenditGiftCardsJob);

    $this->info('Zendit Gift Card and Brand Sync completed successfully!');
})->purpose('Sync Gift Cards and Brand Assets from Zendit API');
