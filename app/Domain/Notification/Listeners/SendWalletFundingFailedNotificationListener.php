<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Wallet\Events\FundingFailed;

/**
 * Customer-facing notification for failed wallet top-ups.
 *
 * Without this, a customer who tries to fund their wallet and is rejected by
 * the gateway sees no in-app alert - the failure only surfaces as a "FAILED"
 * badge on the transactions page if they happen to look there. Pair with the
 * successful credit listener so both outcomes notify the user.
 */
class SendWalletFundingFailedNotificationListener
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher
    ) {}

    public function handle(FundingFailed $event): void
    {
        $funding = $event->funding;
        $user = $funding->wallet?->user ?? $funding->user;

        if (! $user) {
            return;
        }

        $symbol = $funding->wallet?->currency?->symbol() ?? '';
        $amount = number_format((float) $funding->amount, 2);
        $currency = $funding->wallet?->currency?->value ?? '';

        $this->dispatcher->dispatch(
            user: $user,
            title: 'Wallet top-up failed',
            message: "We could not complete your {$symbol}{$amount} {$currency} wallet top-up. No funds were charged. Try again or use a different payment method.",
            category: 'wallet',
            metadata: [
                'funding_reference' => $funding->reference,
                'reason' => $event->reason,
            ],
            idempotencyKey: "funding-failed-{$funding->id}",
        );
    }
}
