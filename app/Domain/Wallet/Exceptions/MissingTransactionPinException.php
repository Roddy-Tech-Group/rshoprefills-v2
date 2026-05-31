<?php

namespace App\Domain\Wallet\Exceptions;

use Exception;

class MissingTransactionPinException extends Exception
{
    // Exception thrown when a user attempts a wallet transaction without a PIN
}
