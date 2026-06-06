<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Mail\AdminNewOrderAlertMail;
use App\Domain\Notification\Mail\OrderPlacedMail;
use App\Domain\Notification\Mail\RefundProcessedMail;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Order\Events\PaymentConfirmed;
use App\Domain\Order\Events\RefundIssued;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

/**
 * Queued so notification work never runs inside the payment-confirmation DB
 * transaction (VerifyPaymentJob). Previously a throw here rolled back the whole
 * confirmation; now a failed notification only retries its own job and can never
 * undo a captured payment.
 */
class SendOrderConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationDispatcher $dispatcher
    ) {}

    /**
     * Handle PaymentConfirmed.
     */
    public function onPaymentConfirmed(PaymentConfirmed $event): void
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

        // 2. Check dynamic suspicious transaction limit (fallback 5000.0).
        // Compare against the USD source-of-truth, not the raw display amount —
        // total_amount is in the order's display_currency, so comparing a NGN
        // figure against a USD threshold would flag almost every order.
        $threshold = (float) config('notification.limits.suspicious_threshold', 5000.0);
        $isLargeTransaction = ($order->usdTotal() > $threshold);

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
                ? "An exceptionally large order #{$order->order_number} of {$order->total_amount} {$order->display_currency} was placed."
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
            message: "A refund of {$event->amount} {$order->display_currency} has been credited to your wallet for Order #{$order->order_number}.",
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
            PaymentConfirmed::class => 'onPaymentConfirmed',
            RefundIssued::class => 'onRefundIssued',
        ];
    }
}
