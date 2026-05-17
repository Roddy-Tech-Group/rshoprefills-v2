<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Wallet\Events\WalletDebited;
use App\Domain\Notification\Mail\WalletDebitedMail;
use App\Domain\Notification\Services\NotificationDispatcher;

class SendWalletDebitNotificationListener
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher
    ) {}

    public function handle(WalletDebited $event): void
    {
        $transaction = $event->transaction;
        $user = $transaction->wallet->user;

        $this->dispatcher->dispatch(
            user: $user,
            title: 'Wallet Debited',
            message: "Your wallet has been debited by {$transaction->amount} {$transaction->currency->value} for: {$transaction->description}",
            category: 'wallet',
            mailable: new WalletDebitedMail($user, $transaction)
        );
    }
}
