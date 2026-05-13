<?php

namespace App\Domain\Shared\Enums;

/**
 * Supported currencies for wallets and transactions.
 */
enum Currency: string
{
    case NGN = 'NGN';
    case USD = 'USD';
    case GBP = 'GBP';
    case GHS = 'GHS';
    case XAF = 'XAF';

    public function symbol(): string
    {
        return match ($this) {
            self::NGN => '₦',
            self::USD => '$',
            self::GBP => '£',
            self::GHS => '₵',
            self::XAF => 'FCFA',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::NGN => 'Nigerian Naira',
            self::USD => 'US Dollar',
            self::GBP => 'British Pound',
            self::GHS => 'Ghanaian Cedi',
            self::XAF => 'Central African CFA Franc',
        };
    }

    public function minimumFundingAmount(): float
    {
        return match ($this) {
            self::NGN => 1000.00,
            self::USD => 5.00,
            self::GBP => 5.00,
            self::GHS => 20.00,
            self::XAF => 3000.00,
        };
    }

    public function decimalPrecision(): int
    {
        // For rendering, all standard fiat here use 2 decimal places.
        // XAF is technically 0 decimals for coins, but 2 is safe for accounting.
        return 2;
    }
}
