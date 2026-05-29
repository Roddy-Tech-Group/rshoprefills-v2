<?php

namespace App\Domain\Transaction\Exceptions;

use Exception;

class DuplicateTransactionException extends Exception
{
    public static function forIdempotencyKey(string $key): self
    {
        return new self("Transaction with idempotency key [{$key}] has already been processed.");
    }
}
