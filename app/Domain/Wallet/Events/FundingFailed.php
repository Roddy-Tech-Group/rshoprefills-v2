<?php

namespace App\Domain\Wallet\Events;

use App\Models\WalletFunding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FundingFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WalletFunding $funding,
        public readonly string $reason
    ) {}
}
