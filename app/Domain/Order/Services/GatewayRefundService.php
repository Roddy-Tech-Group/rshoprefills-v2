<?php

namespace App\Domain\Order\Services;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Transaction\Exceptions\DuplicateTransactionException;
use App\Domain\Wallet\Services\WalletService;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;

/**
 * Refunds for orders paid through a NON-wallet gateway (card / mobile money /
 * crypto). The customer's money settled to us, so when fulfillment fails there
 * is nothing to "reverse" on a wallet payment - we instead credit the
 * customer's wallet with what they paid, honouring the wallet-first refund
 * policy ("failed delivery -> automatic refund to your wallet"). Wallet-funded
 * orders are handled separately by WalletPaymentProvider.
 */
class GatewayRefundService
{
    public function __construct(private WalletService $walletService) {}

    /**
     * Credit the customer's wallet for a single failed item on a non-wallet
     * paid order. Idempotent per item, so retries and the two failure paths
     * (FulfillOrderItemJob + PollPendingFulfillmentJob) can never double-credit.
     */
    public function refundFailedItemToWallet(OrderItem $item): void
    {
        $order = $item->order;
        if (! $order || ! $order->user) {
            return;
        }

        // Only act on a non-wallet payment that actually settled. Wallet
        // payments are released/refunded by WalletPaymentProvider elsewhere.
        $paid = $order->paymentAttempts()
            ->where('gateway', '!=', 'wallet')
            ->where('payment_status', PaymentStatus::Paid)
            ->latest()
            ->first();

        if (! $paid) {
            return;
        }

        // This item's share of what was actually charged (handles multi-item
        // orders where only some items failed).
        $orderTotal = (float) $order->total_amount;
        $paidAmount = (float) $paid->amount;
        $share = $orderTotal > 0
            ? round($paidAmount * ((float) $item->subtotal_amount / $orderTotal), 2)
            : $paidAmount;

        if ($share <= 0) {
            return;
        }

        $currency = Currency::tryFrom(strtoupper((string) $paid->currency)) ?? Currency::USD;
        $wallet = $this->walletService->getOrCreateWallet($order->user, $currency);

        try {
            $this->walletService->credit(
                $wallet,
                $share,
                TransactionCategory::Refund,
                "Refund for an undelivered item on order {$order->order_number}",
                "refund-{$item->id}",
                "refund-failed-item-{$item->id}",
            );

            Log::info('Gateway refund credited to wallet', [
                'order' => $order->order_number,
                'item' => $item->id,
                'amount' => $share,
                'currency' => $currency->value,
            ]);
        } catch (DuplicateTransactionException) {
            // Already refunded by an earlier attempt - safe no-op.
        }
    }
}
