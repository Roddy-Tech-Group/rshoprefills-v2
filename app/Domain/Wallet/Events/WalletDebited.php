<?php

namespace App\Domain\Wallet\Events;

use App\Models\WalletTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletDebited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WalletTransaction $transaction
    ) {}
}
