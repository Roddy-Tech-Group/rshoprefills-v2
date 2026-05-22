<?php

namespace App\Listeners;

use App\Domain\Wallet\Events\TransactionPinChanged;
use App\Domain\Wallet\Events\TransactionPinLocked;
use App\Domain\Wallet\Events\TransactionPinResetRequested;
use App\Mail\Wallet\TransactionPinChangedMail;
use App\Mail\Wallet\TransactionPinLockedMail;
use App\Mail\Wallet\TransactionPinResetMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Mail;

class TransactionPinNotificationListener implements ShouldQueue
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            TransactionPinChanged::class,
            [TransactionPinNotificationListener::class, 'handlePinChanged']
        );

        $events->listen(
            TransactionPinLocked::class,
            [TransactionPinNotificationListener::class, 'handlePinLocked']
        );

        $events->listen(
            TransactionPinResetRequested::class,
            [TransactionPinNotificationListener::class, 'handlePinResetRequested']
        );
    }

    public function handlePinChanged(TransactionPinChanged $event): void
    {
        Mail::to($event->user->email)->send(new TransactionPinChangedMail($event->user));
    }

    public function handlePinLocked(TransactionPinLocked $event): void
    {
        Mail::to($event->user->email)->send(new TransactionPinLockedMail($event->user));
    }

    public function handlePinResetRequested(TransactionPinResetRequested $event): void
    {
        Mail::to($event->user->email)->send(new TransactionPinResetMail($event->user, $event->token));
    }
}
