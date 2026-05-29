<?php

namespace App\Domain\Shared\Enums;

/**
 * Wallet transaction direction.
 *
 * credit → Money flowing INTO the wallet (funding, refunds).
 * debit  → Money flowing OUT of the wallet (purchases, withdrawals).
 */
enum WalletTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    /**
     * Get a human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Credit',
            self::Debit => 'Debit',
        };
    }
}
