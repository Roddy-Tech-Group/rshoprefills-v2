<?php

namespace App\Domain\Wallet\Exceptions;

use Exception;

class WalletFundingException extends Exception
{
    public static function alreadyProcessed(string $reference): self
    {
        return new self("Wallet funding [{$reference}] has already been processed.");
    }

    public static function verificationFailed(string $reference, string $reason): self
    {
        return new self("Funding verification failed for [{$reference}]: {$reason}");
    }
}
