<?php

namespace App\Domain\Payment\Events;

use App\Models\PaymentSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSessionFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PaymentSession $session,
        public string $reason
    ) {}
}
