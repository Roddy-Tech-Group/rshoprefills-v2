<?php

namespace App\Domain\Shared\Enums;

/**
 * Supported payment gateways.
 *
 * flutterwave  → Fiat payments (cards, bank transfers, mobile money).
 * nowpayments  → Cryptocurrency payments.
 * wallet       → Internal wallet balance.
 */
enum PaymentGateway: string
{
    case Flutterwave = 'flutterwave';
    case NowPayments = 'nowpayments';
    case Wallet = 'wallet';

    /**
     * Get a human-readable label for the gateway.
     */
    public function label(): string
    {
        return match ($this) {
            self::Flutterwave => 'Flutterwave',
            self::NowPayments => 'NowPayments',
            self::Wallet => 'Wallet',
        };
    }
}
