<?php

namespace App\Domain\Wallet\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public static function forWallet(int $walletId, float $required, float $available): self
    {
        return new self("Wallet [{$walletId}] has insufficient balance. Required: {$required}, Available: {$available}.");
    }
}
