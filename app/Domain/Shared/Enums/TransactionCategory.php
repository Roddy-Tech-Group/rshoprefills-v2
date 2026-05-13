<?php

namespace App\Domain\Shared\Enums;

/**
 * Categories for classifying wallet transactions.
 */
enum TransactionCategory: string
{
    case Funding = 'funding';
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Withdrawal = 'withdrawal';
    case Reversal = 'reversal';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Funding => 'Funding',
            self::Purchase => 'Purchase',
            self::Refund => 'Refund',
            self::Adjustment => 'Adjustment',
            self::Withdrawal => 'Withdrawal',
            self::Reversal => 'Reversal',
            self::Transfer => 'Transfer',
        };
    }
}
