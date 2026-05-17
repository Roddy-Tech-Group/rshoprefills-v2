<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Order\Events\FulfillmentSucceeded;
use App\Domain\Order\Events\FulfillmentFailed;
use App\Domain\Notification\Mail\OrderFulfilledMail;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendFulfillmentNotificationListener
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher
    ) {}

    /**
     * Handle FulfillmentSucceeded.
     */
    public function onFulfillmentSucceeded(FulfillmentSucceeded $event): void
    {
        $item = $event->item;
        $order = $item->order;
        $user = $order->user;

        $this->dispatcher->dispatch(
            user: $user,
            title: 'Your Refill Voucher is Ready!',
            message: "Fulfillment completed for item: {$item->product_name} in order #{$order->order_number}.",
            category: 'order',
            mailable: new OrderFulfilledMail($user, $item)
        );
    }

    /**
     * Handle FulfillmentFailed.
     */
    public function onFulfillmentFailed(FulfillmentFailed $event): void
    {
        $item = $event->item;
        $order = $item->order;
        $user = $order->user;

        // 1. Notify Customer of delay/failure
        $this->dispatcher->dispatch(
            user: $user,
            title: 'Fulfillment Delay Notification',
            message: "We encountered a temporary delay fulfilling {$item->product_name} for order #{$order->order_number}. Our support team has been notified and will resolve it shortly.",
            category: 'order'
        );

        // 2. Alert Admin
        $adminEmail = config('mail.admin_address') ?? 'admin@rshoprefills.com';
        $adminUser = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'System Admin',
                'password' => bcrypt(\Illuminate\Support\Str::random(16)),
            ]
        );

        $this->dispatcher->dispatch(
            user: $adminUser,
            title: 'CRITICAL: Refill Fulfillment Failed!',
            message: "Fulfillment failed for order #{$order->order_number}, item ID: {$item->id}. Reason: {$event->reason}. Urgent manual intervention required.",
            category: 'security'
        );
    }

    /**
     * Register listeners for subscription.
     */
    public function subscribe($events): array
    {
        return [
            FulfillmentSucceeded::class => 'onFulfillmentSucceeded',
            FulfillmentFailed::class => 'onFulfillmentFailed',
        ];
    }
}
