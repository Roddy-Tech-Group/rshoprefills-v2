<?php

namespace App\Domain\Payment\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Reserved = 'reserved';
    case Processing = 'processing';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Pending => 'Pending',
            self::Reserved => 'Reserved',
            self::Processing => 'Processing',
            self::Paid => 'Paid',
            self::PartiallyPaid => 'Partially Paid',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
        };
    }
}
