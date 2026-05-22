<?php

namespace App\Jobs;

use App\Models\OrderItem;
use App\Models\Order;
use App\Domain\Fulfillment\Services\FulfillmentProviderFactory;
use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Order\Services\OrderService;
use App\Domain\Order\Events\FulfillmentSucceeded;
use App\Domain\Order\Events\FulfillmentFailed;
use App\Domain\Payment\Providers\WalletPaymentProvider;
use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollPendingFulfillmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20; // Allow polling up to 20 times (20 minutes total)

    public function __construct(protected OrderItem $item) {}

    public function handle(
        FulfillmentProviderFactory $providerFactory,
        OrderService $orderService,
        WalletPaymentProvider $walletProvider
    ): void {
        $item = OrderItem::find($this->item->id);
        if (!$item || !in_array($item->fulfillment_status, [FulfillmentStatus::Processing, FulfillmentStatus::Delayed])) {
            return;
        }

        try {
            $provider = $providerFactory->getProvider($item->provider_name);
            $result = $provider->verifyStatus($item);

            $status = $result['status'];

            DB::transaction(function () use ($item, $status, $result, $provider, $orderService, $walletProvider) {
                if ($status === FulfillmentStatus::Fulfilled) {
                    $item->fulfillment_status = FulfillmentStatus::Fulfilled;
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
                } elseif ($status === FulfillmentStatus::Failed) {
                    $item->fulfillment_status = FulfillmentStatus::Failed;
                    $item->failed_at = now();
                    $item->save();

                    FulfillmentFailed::dispatch($item, 'Provider verification returned failed status');

                    // Handle refund safety
                    $order = $item->order;
                    $walletPayment = $order->paymentAttempts()
                        ->where('gateway', 'wallet')
                        ->whereIn('payment_status', [PaymentStatus::Reserved, PaymentStatus::Paid])
                        ->first();

                    if ($walletPayment) {
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
                            if ($walletPayment->payment_status !== PaymentStatus::Reserved) {
                                $walletProvider->refundPayment($walletPayment, $item->subtotal_amount);
                            }
                        }
                    }
                } else {
                    // Still pending/processing, retry after a delay
                    $this->release(60); // Release back to queue in 60 seconds
                }
            });
        } catch (\Exception $e) {
            Log::error("Fulfillment status polling exception: " . $e->getMessage());
            $this->release(60);
        }
    }
}
