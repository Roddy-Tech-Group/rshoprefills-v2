<?php

namespace App\Domain\Fulfillment\Console;

use App\Jobs\FulfillOrderItemJob;
use App\Models\OrderItem;
use Illuminate\Console\Command;

class RetryFulfillmentCommand extends Command
{
    protected $signature = 'fulfillment:retry {item?} {--all-failed}';
    protected $description = 'Manually retry fulfillment for a failed order item';

    public function handle()
    {
        $itemId = $this->argument('item');
        $allFailed = $this->option('all-failed');

        if ($allFailed) {
            $items = OrderItem::where('fulfillment_status', \App\Domain\Fulfillment\Enums\FulfillmentStatus::Failed)->get();
            $this->info("Found {$items->count()} failed items. Re-dispatching...");
            
            foreach ($items as $item) {
                FulfillOrderItemJob::dispatch($item);
            }
            
            $this->info('Done.');
            return;
        }

        if (! $itemId) {
            $this->error('Please provide an item ID or use --all-failed flag.');
            return;
        }

        $item = OrderItem::find($itemId);

        if (! $item) {
            $this->error("OrderItem {$itemId} not found.");
            return;
        }

        if ($item->fulfillment_status === \App\Domain\Fulfillment\Enums\FulfillmentStatus::Fulfilled) {
            $this->warn("OrderItem {$itemId} is already fulfilled.");
            return;
        }

        $this->info("Re-dispatching FulfillOrderItemJob for item {$itemId}...");
        FulfillOrderItemJob::dispatch($item);
        $this->info('Dispatched.');
    }
}


