<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

\Illuminate\Support\Facades\Schedule::job(new \App\Domain\Wallet\Jobs\SyncExchangeRatesJob)->hourly();
\Illuminate\Support\Facades\Schedule::job(new \App\Domain\Wallet\Jobs\ReconcilePendingFundingsJob)->hourly();

Artisan::command('zendit:sync-giftcards', function () {
    $this->info('Setting default queue connection to sync...');
    config(['queue.default' => 'sync']);

    $this->info('Dispatching SyncZenditGiftCardsJob synchronously...');
    dispatch(new \App\Jobs\SyncZenditGiftCardsJob);

    $this->info('Zendit Gift Card and Brand Sync completed successfully!');
})->purpose('Sync Gift Cards and Brand Assets from Zendit API');
