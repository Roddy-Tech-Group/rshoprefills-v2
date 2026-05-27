<?php

namespace App\Jobs;

use App\Domain\Rewards\Services\RewardEngine;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrderRewardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RewardEngine $rewardEngine): void
    {
        // Double-check order status just to be safe
        if ($this->order->order_status === \App\Domain\Order\Enums\OrderStatus::Completed) {
            $rewardEngine->processOrderRewards($this->order);
        }
    }
}
