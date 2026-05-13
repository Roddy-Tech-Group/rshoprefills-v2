<?php

namespace App\Domain\Shared\Enums;

/**
 * Represents the lifecycle status of a payment transaction.
 *
 * pending    → Payment initiated, awaiting gateway confirmation.
 * processing → Gateway processing (e.g., crypto confirmations).
 * completed  → Payment successfully received.
 * failed     → Payment failed or rejected by gateway.
 * refunded   → Payment reversed/refunded.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }
}
