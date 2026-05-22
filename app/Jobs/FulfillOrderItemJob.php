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

        // Bug #3 fix: wrap the entire guard + status-update in a transaction so the
        // lockForUpdate() holds for the duration of the idempotency check. Previously,
        // the lock was released immediately after the first fetch, allowing a concurrent
        // retry to read stale state and dispatch a duplicate fulfillment to Zendit.
        $shouldProceed = DB::transaction(function () {
            $item = OrderItem::where('id', $this->item->id)->lockForUpdate()->first();
            if (!$item) {
                return false;
            }

            // Idempotency: skip if already fulfilled or currently in-flight with a reference
            if ($item->fulfillment_status === FulfillmentStatus::Fulfilled) {
                return false;
            }
            if ($item->fulfillment_status === FulfillmentStatus::Processing && $item->fulfillment_reference) {
                // Already dispatched to Zendit — let PollPendingFulfillmentJob handle it
                return false;
            }

            $item->fulfillment_status = FulfillmentStatus::Processing;
            $item->save();

            return true;
        });

        if (!$shouldProceed) {
            return;
        }

        // Reload a fresh copy after the transaction above released its lock
        $item = OrderItem::find($this->item->id);

        FulfillmentQueued::dispatch($item);

        try {
            $provider = $providerFactory->getProvider($item->provider_name);
            $result = $provider->fulfill($item);

            DB::transaction(function () use ($item, $result, $orderService, $walletProvider, $provider) {
                // Bug #3 fix: re-fetch with a lock inside this transaction so we are
                // operating on the latest DB state, not a stale in-memory object.
                $item = OrderItem::where('id', $item->id)->lockForUpdate()->first();
                if (!$item) {
                    return;
                }

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

                    // Handle wallet reversal if wallet checkout and all items failed
                    $this->handleFailureReversal($item, $walletProvider);
                }
            });

        } catch (\Exception $e) {
            Log::error("Fulfillment job crashed for item {$item->id}: " . $e->getMessage());
            
            DB::transaction(function () use ($item, $walletProvider, $e) {
                $freshItem = OrderItem::where('id', $item->id)->lockForUpdate()->first();
                if (!$freshItem || $freshItem->fulfillment_status === FulfillmentStatus::Fulfilled) {
                    return;
                }
                $freshItem->fulfillment_status = FulfillmentStatus::Failed;
                $freshItem->failed_at = now();
                $freshItem->save();

                FulfillmentFailed::dispatch($freshItem, $e->getMessage());
                $this->handleFailureReversal($freshItem, $walletProvider);
            });

            throw $e; // re-throw so Laravel can schedule retries
        }
    }

    /**
     * Bug #7 fix: Handle permanent job failure after all retries are exhausted.
     * Without this, items stay stuck in 'processing' forever with no refund.
     */
    public function failed(\Throwable $e): void
    {
        Log::critical("FulfillOrderItemJob permanently failed for item {$this->item->id}: " . $e->getMessage());

        $walletProvider = app(WalletPaymentProvider::class);

        DB::transaction(function () use ($e, $walletProvider) {
            $item = OrderItem::where('id', $this->item->id)->lockForUpdate()->first();
            if (!$item || $item->fulfillment_status === FulfillmentStatus::Fulfilled) {
                return;
            }

            $item->fulfillment_status = FulfillmentStatus::Failed;
            $item->failed_at = now();
            $item->save();

            FulfillmentFailed::dispatch($item, 'Job permanently failed: ' . $e->getMessage());
            $this->handleFailureReversal($item, $walletProvider);
        });
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
            // Check if any other item in order was successfully fulfilled or still processing.
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
                // Partial fulfillment: refund only this item's share
                $refundAmount = $item->subtotal_amount;
                if ($walletPayment->payment_status === PaymentStatus::Paid) {
                    $walletProvider->refundPayment($walletPayment, $refundAmount);
                }
                // If Reserved, the full reservation will be settled when remaining items resolve.
            }
        }
    }
}
