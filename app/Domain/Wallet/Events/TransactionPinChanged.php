<?php

namespace App\Domain\Wallet\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionPinChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user)
    {
    }
}
