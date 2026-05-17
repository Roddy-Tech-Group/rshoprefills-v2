<?php

namespace App\Domain\Order\Events;

use App\Models\OrderItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FulfillmentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public OrderItem $item) {}
}
