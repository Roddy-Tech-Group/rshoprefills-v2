<?php

namespace App\Listeners;

use App\Domain\Wallet\Events\TransactionPinChanged;
use App\Domain\Wallet\Events\TransactionPinLocked;
use App\Domain\Wallet\Events\TransactionPinResetRequested;
use App\Mail\Wallet\TransactionPinChangedMail;
use App\Mail\Wallet\TransactionPinLockedMail;
use App\Mail\Wallet\TransactionPinResetMail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
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
        $this->safeSend($event->user->email, new TransactionPinChangedMail($event->user));
    }

    public function handlePinLocked(TransactionPinLocked $event): void
    {
        $this->safeSend($event->user->email, new TransactionPinLockedMail($event->user));
    }

    public function handlePinResetRequested(TransactionPinResetRequested $event): void
    {
        $this->safeSend($event->user->email, new TransactionPinResetMail($event->user, $event->token));
    }

    /**
     * Send a PIN notification email without letting a mail-provider failure break
     * the PIN operation that triggered it. Delivery is best-effort and logged.
     */
    private function safeSend(string $email, Mailable $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('Transaction PIN email failed to send.', [
                'email' => $email,
                'mailable' => $mailable::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
