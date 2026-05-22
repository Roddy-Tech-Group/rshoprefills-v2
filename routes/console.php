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

// Bug #4 fix: Fulfillment fallback sweeper.
//
// For non-wallet gateways (card, bank, crypto), fulfillment is triggered by the
// Flutterwave/NowPayments webhook → VerifyPaymentJob chain. If the webhook is
// delayed, dropped, or the queue worker was offline at the time of payment, all
// order items remain at NotStarted indefinitely — no refund fires, no voucher is
// delivered, and the customer's money appears lost.
//
// This sweeper runs every 5 minutes and re-dispatches FulfillOrderItemJob for
// any paid order whose items are still NotStarted after a 10-minute grace window.
\Illuminate\Support\Facades\Schedule::call(function () {
    $cutoff = now()->subMinutes(10);

    $orphanedOrders = \App\Models\Order::query()
        ->where('payment_status', \App\Domain\Payment\Enums\PaymentStatus::Paid)
        ->where('fulfillment_status', \App\Domain\Fulfillment\Enums\FulfillmentStatus::NotStarted)
        ->where('placed_at', '<=', $cutoff)
        ->with('items')
        ->get();

    foreach ($orphanedOrders as $order) {
        foreach ($order->items as $item) {
            if ($item->fulfillment_status === \App\Domain\Fulfillment\Enums\FulfillmentStatus::NotStarted) {
                \Illuminate\Support\Facades\Log::warning("Fulfillment sweeper: re-dispatching FulfillOrderItemJob for orphaned item {$item->id} on order {$order->order_number}");
                \App\Jobs\FulfillOrderItemJob::dispatch($item);
            }
        }
    }
})->everyFiveMinutes()->name('fulfillment:rescue-orphaned-orders');

// Enterprise Reconciliation Engine Scheduling
\Illuminate\Support\Facades\Schedule::command('reconcile:wallet-balances')->dailyAt('02:00');
\Illuminate\Support\Facades\Schedule::command('reconcile:orphaned-sessions')->hourly();

// Enterprise Provider Monitoring
\Illuminate\Support\Facades\Schedule::command('zendit:check-balance --threshold=500')->hourly();

