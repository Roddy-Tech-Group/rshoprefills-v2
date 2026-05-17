<?php

namespace App\Domain\Order\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public float $amount,
        public string $reason
    ) {}
}
