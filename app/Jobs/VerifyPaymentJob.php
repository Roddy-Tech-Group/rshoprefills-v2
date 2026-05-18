<?php

namespace App\Jobs;

use App\Models\PaymentAttempt;
use App\Models\Order;
use App\Domain\Payment\Services\PaymentGatewayFactory;
use App\Domain\Order\Services\OrderService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Order\Events\PaymentConfirmed;
use App\Domain\Order\Events\PaymentFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 10;

    public function __construct(protected PaymentAttempt $attempt) {}

    public function handle(
        PaymentGatewayFactory $gatewayFactory,
        OrderService $orderService
    ): void {
        Log::info("VerifyPaymentJob: checking payment status for attempt {$this->attempt->id}");

        $attempt = PaymentAttempt::find($this->attempt->id);
        if (!$attempt || in_array($attempt->payment_status, [PaymentStatus::Paid, PaymentStatus::Failed])) {
            return;
        }

        try {
            $provider = $gatewayFactory->getProvider($attempt->gateway);
            $isPaid = $provider->verifyPayment($attempt);

            if ($isPaid) {
                Log::info("VerifyPaymentJob: payment confirmed for attempt {$attempt->id}");

                // 1. Transition the order's payment status to Paid
                $orderService->transitionPaymentStatus($attempt->order, PaymentStatus::Paid, $attempt->verification_payload);

                // 2. Synchronize active PaymentSession model if exists
                $attempt->load('paymentSession');
                if ($attempt->paymentSession) {
                    $sessionService = app(\App\Domain\Payment\Services\PaymentSessionService::class);
                    $sessionService->confirmSession($attempt->paymentSession, [
                        'transaction_id' => $attempt->gateway_reference,
                        'payload' => $attempt->verification_payload,
                    ]);
                }

                // 3. Refresh Order to verify paid status
                $order = Order::find($attempt->order_id);

                PaymentConfirmed::dispatch($order, $attempt);

                // 4. Dispatch fulfillment jobs for each order item
                foreach ($order->items as $item) {
                    FulfillOrderItemJob::dispatch($item);
                }
            } else {
                Log::warning("VerifyPaymentJob: payment verification returned unpaid/pending for attempt {$attempt->id}");
            }
        } catch (\Exception $e) {
            Log::error("Payment verification failed for attempt {$attempt->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
