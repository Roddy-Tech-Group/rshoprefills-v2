<?php

namespace App\Domain\Order\Events;

use App\Models\Order;
use App\Models\PaymentAttempt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public PaymentAttempt $attempt
    ) {}
}
