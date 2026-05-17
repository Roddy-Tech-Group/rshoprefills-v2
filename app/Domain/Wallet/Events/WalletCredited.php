<?php

namespace App\Domain\Wallet\Events;

use App\Models\WalletTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletCredited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WalletTransaction $transaction
    ) {}
}
