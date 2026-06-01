<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Mail\WalletFundedMail;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Wallet\Events\WalletCredited;

class SendWalletCreditNotificationListener
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher
    ) {}

    public function handle(WalletCredited $event): void
    {
        $transaction = $event->transaction;
        $user = $transaction->wallet->user;

        // Skip refund credits because the RefundIssued listener handles its own email
        if ($transaction->transaction_category === TransactionCategory::Refund) {
            return;
        }

        if ($transaction->transaction_category === TransactionCategory::Funding) {
            $this->dispatcher->dispatch(
                user: $user,
                title: 'Wallet Funded Successfully',
                message: "Your wallet has been funded with {$transaction->amount} {$transaction->currency->value}.",
                category: 'wallet',
                mailable: new WalletFundedMail($user, $transaction)
            );
        } else {
            // General Credit (e.g. Transfer, Adjustment)
            $this->dispatcher->dispatch(
                user: $user,
                title: 'Wallet Credited',
                message: "Your wallet has been credited with {$transaction->amount} {$transaction->currency->value}. Reason: {$transaction->description}",
                category: 'wallet'
            );
        }
    }
}
