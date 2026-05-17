<?php

namespace App\Domain\Order\Services;

use App\Models\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Transition payment status and check for subsequent order lifecycle transitions.
     */
    public function transitionPaymentStatus(Order $order, PaymentStatus $newStatus, ?array $payload = null): void
    {
        DB::transaction(function () use ($order, $newStatus, $payload) {
            $order = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            
            $order->payment_status = $newStatus;

            if ($newStatus === PaymentStatus::Paid) {
                if ($order->order_status !== OrderStatus::Completed) {
                    $order->order_status = OrderStatus::Processing;
                }
                $order->placed_at = $order->placed_at ?? now();
            } elseif ($newStatus === PaymentStatus::Failed) {
                $order->order_status = OrderStatus::Failed;
                $order->failed_at = now();
            }

            if ($payload) {
                $order->metadata = array_merge($order->metadata ?? [], ['payment_transition_payload' => $payload]);
            }

            $order->save();
        });
    }

    /**
     * Transition fulfillment status and check for overall order completion.
     */
    public function transitionFulfillmentStatus(Order $order, FulfillmentStatus $newStatus): void
    {
        DB::transaction(function () use ($order, $newStatus) {
            $order = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            
            $order->fulfillment_status = $newStatus;

            if ($newStatus === FulfillmentStatus::Fulfilled) {
                $order->order_status = OrderStatus::Completed;
                $order->completed_at = now();
            } elseif ($newStatus === FulfillmentStatus::Failed) {
                $order->order_status = OrderStatus::Failed;
                $order->failed_at = now();
            }

            $order->save();
        });
    }
}
