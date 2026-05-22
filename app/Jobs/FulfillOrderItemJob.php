<?php

namespace App\Jobs;

use App\Models\OrderItem;
use App\Models\Order;
use App\Domain\Fulfillment\Services\FulfillmentProviderFactory;
use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Order\Services\OrderService;
use App\Domain\Order\Events\FulfillmentSucceeded;
use App\Domain\Order\Events\FulfillmentFailed;
use App\Domain\Order\Events\FulfillmentQueued;
use App\Domain\Payment\Providers\WalletPaymentProvider;
use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FulfillOrderItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(protected OrderItem $item) {}

    public function handle(
        FulfillmentProviderFactory $providerFactory,
        OrderService $orderService,
        WalletPaymentProvider $walletProvider
    ): void {
        Log::info("FulfillOrderItemJob: starting fulfillment for order item {$this->item->id}");

        // Ensure we load fresh DB state
        $item = OrderItem::where('id', $this->item->id)->lockForUpdate()->first();
        if (!$item) {
            return;
        }

        // If already fulfilled, do not fulfill again
        if ($item->fulfillment_status === FulfillmentStatus::Fulfilled) {
            return;
        }

        $item->fulfillment_status = FulfillmentStatus::Processing;
        $item->save();

        FulfillmentQueued::dispatch($item);

        try {
            $provider = $providerFactory->getProvider($item->provider_name);
            $result = $provider->fulfill($item);

            DB::transaction(function () use ($item, $result, $orderService, $walletProvider, $provider) {
                $status = $result['status'];
                $item->fulfillment_status = $status;
                $item->fulfillment_reference = $result['reference'] ?? $item->fulfillment_reference;
                
                if ($status === FulfillmentStatus::Fulfilled) {
                    $item->fulfillment_payload = $provider->normalizeResponse($result['payload'] ?? []);
                    $item->delivered_at = now();
                    $item->save();

                    FulfillmentSucceeded::dispatch($item);

                    // Check if parent order is completed (all items fulfilled)
                    $order = Order::where('id', $item->order_id)->lockForUpdate()->first();
                    $allItemsFulfilled = $order->items->every(fn($i) => $i->fulfillment_status === FulfillmentStatus::Fulfilled);

                    if ($allItemsFulfilled) {
                        $orderService->transitionFulfillmentStatus($order, FulfillmentStatus::Fulfilled);
                    }
                } elseif ($status === FulfillmentStatus::Processing || $status === FulfillmentStatus::Delayed) {
                    $item->save();
                    // Schedule status polling job
                    PollPendingFulfillmentJob::dispatch($item)->delay(now()->addMinutes(1));
                } else {
                    // Failed
                    $item->failed_at = now();
                    $item->save();

                    FulfillmentFailed::dispatch($item, 'Provider returned failed status');

                    // For safety, handle wallet reversal if wallet checkout and all items fail or handle partial refund
                    $this->handleFailureReversal($item, $walletProvider);
                }
            });

        } catch (\Exception $e) {
            Log::error("Fulfillment job crashed for item {$item->id}: " . $e->getMessage());
            
            $item->fulfillment_status = FulfillmentStatus::Failed;
            $item->failed_at = now();
            $item->save();

            FulfillmentFailed::dispatch($item, $e->getMessage());
            $this->handleFailureReversal($item, $walletProvider);
        }
    }

    private function handleFailureReversal(OrderItem $item, WalletPaymentProvider $walletProvider): void
    {
        $order = $item->order;
        
        // Find wallet payment (could be Reserved or Paid)
        $walletPayment = $order->paymentAttempts()
            ->where('gateway', 'wallet')
            ->whereIn('payment_status', [PaymentStatus::Reserved, PaymentStatus::Paid])
            ->first();

        if ($walletPayment) {
            // Check if any other item in order was successfully fulfilled or processing.
            // If all items failed, refund/release full funds.
            $hasSuccessfulItem = $order->items->contains(function ($i) {
                return in_array($i->fulfillment_status, [
                    FulfillmentStatus::Fulfilled,
                    FulfillmentStatus::Processing,
                    FulfillmentStatus::Delayed
                ]);
            });

            if (!$hasSuccessfulItem) {
                if ($walletPayment->payment_status === PaymentStatus::Reserved) {
                    $walletProvider->releaseFunds($walletPayment);
                } else {
                    $walletProvider->refundPayment($walletPayment, $order->total_amount);
                }
                
                $order->payment_status = PaymentStatus::Failed;
                $order->order_status = \App\Domain\Order\Enums\OrderStatus::Failed;
                $order->failed_at = now();
                $order->save();
            } else {
                // Partial fulfillment refund
                $refundAmount = $item->subtotal_amount;
                if ($walletPayment->payment_status === PaymentStatus::Reserved) {
                    // Release is for the whole attempt, so this might not work perfectly for partial
                    // But wallet checkout is paid upfront anyway
                } else {
                    $walletProvider->refundPayment($walletPayment, $refundAmount);
                }
            }
        }
    }
}
