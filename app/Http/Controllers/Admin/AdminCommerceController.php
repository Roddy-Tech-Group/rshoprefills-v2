<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Events\RefundIssued;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentGatewayFactory;
use App\Http\Controllers\Controller;
use App\Jobs\FulfillOrderItemJob;
use App\Models\FulfillmentLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;

class AdminCommerceController extends Controller
{
    public function listOrders(Request $request)
    {
        $query = Order::with(['user', 'items']);

        if ($request->filled('status')) {
            $query->where('order_status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$search}%")->orWhereRelation('user', 'email', 'like', "%{$search}%"));
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function listPayments(Request $request)
    {
        $query = PaymentAttempt::with(['user', 'order']);

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->input('gateway'));
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function listFulfillmentLogs(Request $request)
    {
        return response()->json(FulfillmentLog::latest()->paginate(15));
    }

    public function retryFulfillment(string $itemId)
    {
        $item = OrderItem::findOrFail($itemId);

        // Can only retry failed or queued ones
        if ($item->fulfillment_status->value === 'fulfilled') {
            return response()->json(['message' => 'This item is already successfully fulfilled.'], 400);
        }

        FulfillOrderItemJob::dispatch($item);

        return response()->json(['message' => 'Fulfillment job successfully re-queued.']);
    }

    public function refundOrder(Request $request, string $orderId)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $order = Order::findOrFail($orderId);
        $amount = (float) $validated['amount'];

        if ($amount > $order->total_amount) {
            return response()->json(['message' => 'Refund amount cannot exceed the order total amount.'], 400);
        }

        // Get successful or active payment attempt
        $attempt = $order->paymentAttempts()
            ->whereIn('payment_status', [PaymentStatus::Paid, PaymentStatus::Reserved])
            ->first();

        if (! $attempt) {
            return response()->json(['message' => 'No successful payment attempt found for this order.'], 400);
        }

        try {
            $factory = app(PaymentGatewayFactory::class);
            $provider = $factory->getProvider($attempt->gateway);

            // Trigger refund in gateway
            $provider->refundPayment($attempt, $amount);

            // Mark order status
            $order->order_status = OrderStatus::Refunded;
            $order->payment_status = PaymentStatus::Refunded;
            $order->save();

            RefundIssued::dispatch($order, $amount, $validated['reason']);

            return response()->json(['message' => 'Refund processed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Refund operation failed: '.$e->getMessage()], 400);
        }
    }
}
