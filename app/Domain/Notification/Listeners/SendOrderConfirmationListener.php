<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Mail\AdminNewOrderAlertMail;
use App\Domain\Notification\Mail\OrderPlacedMail;
use App\Domain\Notification\Mail\RefundProcessedMail;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Order\Events\OrderPlaced;
use App\Domain\Order\Events\RefundIssued;
use App\Models\User;
use Illuminate\Support\Str;

class SendOrderConfirmationListener
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher
    ) {}

    /**
     * Handle OrderPlaced.
     */
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $order->user;

        // 1. Send customer confirmation
        $this->dispatcher->dispatch(
            user: $user,
            title: 'Order Placed successfully',
            message: "Your order #{$order->order_number} has been received and is being processed.",
            category: 'order',
            mailable: new OrderPlacedMail($user, $order)
        );

        // 2. Check dynamic suspicious transaction limit (fallback 5000.0)
        $threshold = (float) config('notification.limits.suspicious_threshold', 5000.0);
        $isLargeTransaction = ($order->total_amount > $threshold);

        // 3. Notify Admin of new order or suspicious transaction
        $adminEmail = config('mail.admin_address') ?? 'admin@rshoprefills.com';
        $adminUser = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'System Admin',
                'password' => bcrypt(Str::random(16)),
            ]
        );

        $this->dispatcher->dispatch(
            user: $adminUser,
            title: $isLargeTransaction ? 'CRITICAL: Large Transaction Alert' : 'New Order Notification',
            message: $isLargeTransaction
                ? "An exceptionally large order #{$order->order_number} of {$order->total_amount} {$order->currency->value} was placed."
                : "A new order #{$order->order_number} was placed.",
            category: 'security',
            mailable: new AdminNewOrderAlertMail($order, $isLargeTransaction)
        );
    }

    /**
     * Handle RefundIssued.
     */
    public function onRefundIssued(RefundIssued $event): void
    {
        $order = $event->order;
        $user = $order->user;

        $this->dispatcher->dispatch(
            user: $user,
            title: 'Refund Processed',
            message: "A refund of {$event->amount} {$order->currency->value} has been credited to your wallet for Order #{$order->order_number}.",
            category: 'order',
            mailable: new RefundProcessedMail($user, $order, $event->amount, $event->reason)
        );
    }

    /**
     * Register listeners for subscription.
     */
    public function subscribe($events): array
    {
        return [
            OrderPlaced::class => 'onOrderPlaced',
            RefundIssued::class => 'onRefundIssued',
        ];
    }
}
