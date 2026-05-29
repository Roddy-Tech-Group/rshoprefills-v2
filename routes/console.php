<?php

use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Notification\Jobs\RetryFailedNotificationsJob;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Jobs\ExpireStalePaymentSessionsJob;
use App\Domain\Wallet\Jobs\ReconcilePendingFundingsJob;
use App\Domain\Wallet\Jobs\SyncExchangeRatesJob;
use App\Jobs\FulfillOrderItemJob;
use App\Jobs\PollPendingFulfillmentJob;
use App\Jobs\SyncZenditGiftCardsJob;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new SyncExchangeRatesJob)->hourly();
Schedule::job(new ReconcilePendingFundingsJob)->hourly();

// Auto-retry failed customer notifications. The job picks up notifications
// that have been in the Failed state for at least 5 minutes (transient outage
// backoff) and re-dispatches them, capped at MAX_AUTO_RETRIES per row so a
// permanently broken recipient doesn't churn forever. Admin retains a manual
// per-row Retry button on /admin/notifications for cases where the auto-sweep
// has exhausted its retries.
Schedule::job(new RetryFailedNotificationsJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->name('notifications:retry-failed');

// Sweep PaymentSessions past their 15-minute TTL and cancel the linked Order /
// WalletFunding, so half-finished checkout attempts don't sit Pending on the
// customer's dashboard forever. Five-minute cadence — the dead state shows for
// at most ~5 min between cron ticks.
Schedule::job(new ExpireStalePaymentSessionsJob)->everyFiveMinutes();

// Same job, on demand. Useful after a debugging session left dozens of Pending
// orders, or any time you want to flush stale sessions without waiting for cron.
Artisan::command('payments:expire-stale', function () {
    $this->info('Sweeping stale payment sessions...');
    dispatch_sync(new ExpireStalePaymentSessionsJob);
    $this->info('Done.');
})->purpose('Expire payment sessions past their TTL and cancel their orders/fundings');

Artisan::command('zendit:sync-giftcards', function () {
    $this->info('Setting default queue connection to sync...');
    config(['queue.default' => 'sync']);

    $this->info('Dispatching SyncZenditGiftCardsJob synchronously...');
    dispatch(new SyncZenditGiftCardsJob);

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
Schedule::call(function () {
    $cutoff = now()->subMinutes(10);

    $orphanedOrders = Order::query()
        ->where('payment_status', PaymentStatus::Paid)
        ->where('fulfillment_status', FulfillmentStatus::NotStarted)
        ->where('placed_at', '<=', $cutoff)
        ->with('items')
        ->get();

    foreach ($orphanedOrders as $order) {
        foreach ($order->items as $item) {
            if ($item->fulfillment_status === FulfillmentStatus::NotStarted) {
                Log::warning("Fulfillment sweeper: re-dispatching FulfillOrderItemJob for orphaned item {$item->id} on order {$order->order_number}");
                FulfillOrderItemJob::dispatch($item);
            }
        }
    }
})->everyFiveMinutes()->name('fulfillment:rescue-orphaned-orders');

// Pending-fulfillment poll sweeper.
//
// When a provider returns Processing (Zendit top-ups + eSIMs, Airalo) the
// fulfillment job queues a PollPendingFulfillmentJob with a 10s delay to
// check status. On `QUEUE_CONNECTION=sync` that delay is moot — the job
// runs inline and then never retries, so items stick at `processing`
// forever. This sweeper kicks them every minute, with a 30-minute upper
// bound so a stuck item doesn't churn forever (the poll job itself stops
// re-releasing after 120 tries / 20 minutes).
Schedule::call(function () {
    $cutoff = now()->subMinutes(30);

    OrderItem::query()
        ->whereIn('fulfillment_status', [FulfillmentStatus::Processing, FulfillmentStatus::Delayed])
        ->where('updated_at', '>=', $cutoff)
        ->get()
        ->each(function (OrderItem $item) {
            dispatch_sync(new PollPendingFulfillmentJob($item));
        });
})->everyMinute()->name('fulfillment:poll-pending')->withoutOverlapping();

// Enterprise Reconciliation Engine Scheduling
Schedule::command('reconcile:wallet-balances')->dailyAt('02:00');
Schedule::command('reconcile:orphaned-sessions')->hourly();

// Enterprise Provider Monitoring
Schedule::command('zendit:check-balance --threshold=500')->hourly();
