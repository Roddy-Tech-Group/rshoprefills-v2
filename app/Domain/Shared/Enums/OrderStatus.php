<?php

namespace App\Domain\Shared\Enums;

/**
 * Represents the lifecycle status of an order.
 *
 * pending     → Order created, awaiting payment.
 * processing  → Payment received, fulfillment in progress.
 * completed   → All items fulfilled and delivered.
 * failed      → Fulfillment or payment failed.
 * refunded    → Order refunded after completion.
 * cancelled   → Order cancelled before fulfillment.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

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
            self::Cancelled => 'Cancelled',
        };
    }
}
