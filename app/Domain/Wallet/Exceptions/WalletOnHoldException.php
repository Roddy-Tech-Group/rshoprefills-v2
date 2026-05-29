<?php

namespace App\Domain\Wallet\Exceptions;

use RuntimeException;

/**
 * Thrown when a debit is attempted against a wallet an admin has placed on
 * hold (Wallet.is_active = false). The message is customer-friendly so the
 * payment surface can render it verbatim without wrapping it in technical
 * jargon — see the PaymentSessionController catch block.
 */
class WalletOnHoldException extends RuntimeException
{
    public function __construct(string $message = 'Your wallet is currently on hold. Please contact support if you believe this is a mistake.')
    {
        parent::__construct($message);
    }
}
