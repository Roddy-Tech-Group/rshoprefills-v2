<?php

namespace App\Domain\Payment\Jobs;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Services\OrderService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentSessionService;
use App\Domain\Shared\Enums\FundingStatus;
use App\Models\PaymentSession;
use App\Models\WalletFunding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sweep PaymentSessions past their expires_at and still pre-terminal, transitioning
 * them to 'expired' and cascading the cancellation to the linked Order (-> Cancelled)
 * or WalletFunding (-> Failed).
 *
 * Without this, a customer who closes the tab mid-payment (or fails a card charge in
 * a way that doesn't fail-the-session synchronously) leaves an "RSR-..." order sitting
 * in Pending on their dashboard forever, polluting Recent Orders and the order count.
 *
 * Scheduled in routes/console.php — runs every 5 minutes. Can also be triggered on
 * demand with `php artisan payments:expire-stale` (see the same file).
 */
class ExpireStalePaymentSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Non-terminal session statuses we'll consider for expiry. Matches the set
     * `PaymentSessionService::expireSession()` itself will act on — anything
     * outside this list is already terminal (confirmed / failed / cancelled).
     */
    private const ACTIVE_STATUSES = [
        'awaiting_payment',
        'awaiting_method',
        'awaiting_transfer',
        'awaiting_confirmation',
        'awaiting_customer_action',
    ];

    public function handle(
        PaymentSessionService $paymentSessionService,
        OrderService $orderService
    ): void {
        $stale = PaymentSession::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with('paymentAttempt.order', 'paymentAttempt.payable')
            ->get();

        if ($stale->isEmpty()) {
            return;
        }

        $expired = 0;

        foreach ($stale as $session) {
            try {
                // 1. Expire the session itself — the existing state-machine method,
                //    idempotent if status changed between query and lock.
                $paymentSessionService->expireSession($session);

                // 2. Cascade to the linked Order or WalletFunding so the customer's
                //    dashboard reflects the dead checkout instead of showing Pending
                //    in perpetuity.
                $attempt = $session->paymentAttempt;
                if (! $attempt) {
                    $expired++;

                    continue;
                }

                if ($order = $attempt->order) {
                    // Skip if a late webhook already moved the order past Unpaid.
                    if ($order->payment_status === PaymentStatus::Unpaid) {
                        $orderService->transitionPaymentStatus($order, PaymentStatus::Failed, [
                            'reason' => 'Payment session expired',
                            'expired_at' => $session->expires_at?->toIso8601String(),
                        ]);
                        $order->update(['order_status' => OrderStatus::Cancelled]);
                    }
                } elseif ($attempt->payable_type === WalletFunding::class) {
                    $funding = $attempt->payable;
                    if ($funding && $funding->status !== FundingStatus::Completed) {
                        $funding->update(['status' => FundingStatus::Failed]);
                    }
                }

                $expired++;
            } catch (\Throwable $e) {
                // Don't let one bad session abort the whole sweep.
                Log::error('ExpireStalePaymentSessionsJob: failed for session '.$session->id, [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($expired > 0) {
            Log::info("ExpireStalePaymentSessionsJob: expired {$expired} stale session(s).");
        }
    }
}
