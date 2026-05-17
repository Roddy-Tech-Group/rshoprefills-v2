<?php

namespace App\Domain\Fulfillment\Enums;

enum FulfillmentStatus: string
{
    case NotStarted = 'not_started';
    case Queued = 'queued';
    case Processing = 'processing';
    case Fulfilled = 'fulfilled';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Failed = 'failed';
    case Delayed = 'delayed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::Queued => 'Queued',
            self::Processing => 'Processing',
            self::Fulfilled => 'Fulfilled',
            self::PartiallyFulfilled => 'Partially Fulfilled',
            self::Failed => 'Failed',
            self::Delayed => 'Delayed',
        };
    }
}
