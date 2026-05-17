<?php

namespace App\Listeners;

use App\Domain\Order\Events\OrderPlaced;
use App\Domain\Order\Events\PaymentConfirmed;
use App\Domain\Order\Events\FulfillmentSucceeded;
use App\Domain\Order\Events\FulfillmentFailed;
use App\Domain\Order\Events\RefundIssued;
use Illuminate\Support\Facades\Log;

class CommerceNotificationListener
{
    /**
     * Handle OrderPlaced event.
     */
    public function onOrderPlaced(OrderPlaced $event): void
    {
        Log::info("Notification: Order #{$event->order->order_number} has been successfully placed. Awaiting payment.");
    }

    /**
     * Handle PaymentConfirmed event.
     */
    public function onPaymentConfirmed(PaymentConfirmed $event): void
    {
        Log::info("Notification: Payment of {$event->attempt->amount} {$event->attempt->currency} confirmed for Order #{$event->order->order_number}. Fulfillment initiated.");
    }

    /**
     * Handle FulfillmentSucceeded event.
     */
    public function onFulfillmentSucceeded(FulfillmentSucceeded $event): void
    {
        $item = $event->item;
        $order = $item->order;
        $email = $order->metadata['delivery_email'] ?? $order->user->email;

        Log::info("Notification: Fulfillment success for Order #{$order->order_number}, Item: {$item->id}. Delivery dispatched to {$email}.");
    }

    /**
     * Handle FulfillmentFailed event.
     */
    public function onFulfillmentFailed(FulfillmentFailed $event): void
    {
        Log::critical("Notification: FULFILLMENT FAILURE for Order #{$event->item->order->order_number}, Item: {$event->item->id}. Administrator attention required!");
    }

    /**
     * Handle RefundIssued event.
     */
    public function onRefundIssued(RefundIssued $event): void
    {
        Log::info("Notification: Refund of {$event->amount} issued for Order #{$event->order->order_number}. Reason: {$event->reason}");
    }

    /**
     * Register listeners for subscriber.
     */
    public function subscribe($events): array
    {
        return [
            OrderPlaced::class => 'onOrderPlaced',
            PaymentConfirmed::class => 'onPaymentConfirmed',
            FulfillmentSucceeded::class => 'onFulfillmentSucceeded',
            FulfillmentFailed::class => 'onFulfillmentFailed',
            RefundIssued::class => 'onRefundIssued',
        ];
    }
}
