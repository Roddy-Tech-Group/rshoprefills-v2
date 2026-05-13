<?php

namespace App\Domain\Wallet\Exceptions;

use Exception;

class CurrencyMismatchException extends Exception
{
    public static function mismatch(string $expected, string $provided): self
    {
        return new self("Currency mismatch: Expected {$expected}, but provided {$provided}.");
    }
}
