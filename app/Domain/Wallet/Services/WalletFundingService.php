<?php

namespace App\Domain\Wallet\Services;

use App\Domain\Payment\Services\FlutterwaveService;
use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\FundingStatus;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Transaction\Services\TransactionService;
use App\Domain\Wallet\Exceptions\WalletFundingException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletFunding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the wallet funding lifecycle (initiation, verification, and completion).
 */
class WalletFundingService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly FlutterwaveService $flutterwaveService,
        private readonly WalletService $walletService,
    ) {}

    /**
     * Initialize a funding request and generate a Flutterwave payment link.
     */
    public function initializeFunding(User $user, Wallet $wallet, float $amount, Currency $currency): array
    {
        if ($wallet->currency->value !== $currency->value) {
            throw new \InvalidArgumentException('Funding currency must match wallet currency.');
        }

        if ($amount < $currency->minimumFundingAmount()) {
            throw new \InvalidArgumentException("Minimum funding amount is {$currency->minimumFundingAmount()} {$currency->value}.");
        }

        $reference = $this->transactionService->generateReference('FUND', $currency);

        $funding = WalletFunding::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'reference' => $reference,
            'currency' => $currency->value,
            'amount' => $amount,
            'gateway' => 'flutterwave',
            'status' => FundingStatus::Pending,
        ]);

        // Initialize with Flutterwave
        $flwPayload = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => $currency->value,
            'redirect_url' => url('/dashboard/wallet/funding/callback'),
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'customizations' => [
                'title' => config('app.name').' Wallet Funding',
                'description' => "Fund {$currency->value} wallet",
            ],
        ];

        $initData = $this->flutterwaveService->initializePayment($flwPayload);

        if (! $initData || ! isset($initData['link'])) {
            $funding->update([
                'status' => FundingStatus::Failed,
                'failed_reason' => 'Failed to initialize payment gateway.',
            ]);

            throw new \RuntimeException('Unable to initialize payment gateway. Please try again.');
        }

        return [
            'funding' => $funding,
            'payment_link' => $initData['link'],
        ];
    }

    /**
     * Process a successful webhook event and credit the user's wallet.
     * Uses pessimistic locking to ensure idempotency and prevent double-credits.
     *
     * @throws WalletFundingException
     */
    public function processSuccessfulFunding(string $txRef, string $flwTransactionId, array $rawPayload = []): void
    {
        DB::transaction(function () use ($txRef, $flwTransactionId, $rawPayload) {
            // Lock the funding record to prevent race conditions from duplicate webhooks
            $funding = WalletFunding::where('reference', $txRef)->lockForUpdate()->first();

            if (! $funding) {
                Log::error('Wallet funding record not found.', ['tx_ref' => $txRef]);
                throw new \RuntimeException('Funding record not found.');
            }

            if ($funding->status === FundingStatus::Completed) {
                // Idempotent return: already processed.
                Log::info('Wallet funding already processed.', ['tx_ref' => $txRef]);

                return;
            }

            if ($funding->status === FundingStatus::Failed || $funding->status === FundingStatus::Cancelled) {
                throw WalletFundingException::alreadyProcessed($txRef);
            }

            // Server-to-server verification with Flutterwave
            $flwData = $this->flutterwaveService->verifyTransaction($flwTransactionId);

            if (! $flwData || ($flwData['status'] !== 'successful')) {
                $reason = $flwData['processor_response'] ?? 'Transaction not successful on gateway.';
                $this->failFunding($funding, $reason, $rawPayload);
                throw WalletFundingException::verificationFailed($txRef, $reason);
            }

            // Verify amount and currency to prevent tampering
            if ((float) $flwData['amount'] < (float) $funding->amount || $flwData['currency'] !== $funding->currency->value) {
                $reason = 'Amount or currency mismatch during verification.';
                $this->failFunding($funding, $reason, $rawPayload);
                throw WalletFundingException::verificationFailed($txRef, $reason);
            }

            // Proceed to credit wallet
            $wallet = $funding->wallet;

            $this->walletService->credit(
                wallet: $wallet,
                amount: (float) $funding->amount,
                category: TransactionCategory::Funding,
                description: 'Wallet funded via Flutterwave',
                reference: $funding->reference,
                idempotencyKey: "fund-{$funding->reference}",
                sourceType: 'wallet_fundings',
                sourceId: $funding->id,
                metadata: [
                    'gateway_transaction_id' => $flwTransactionId,
                    'gateway_fee' => $flwData['app_fee'] ?? 0,
                ]
            );

            // Mark funding as completed
            $funding->update([
                'status' => FundingStatus::Completed,
                'gateway_reference' => $flwTransactionId,
                'gateway_payload' => $rawPayload,
                'processed_at' => now(),
            ]);

            Log::info('Wallet funding successfully processed.', ['tx_ref' => $txRef, 'amount' => $funding->amount]);
        });
    }

    /**
     * Mark a funding record as failed.
     */
    public function failFunding(WalletFunding $funding, string $reason, array $payload = []): void
    {
        $funding->update([
            'status' => FundingStatus::Failed,
            'failed_reason' => $reason,
            'gateway_payload' => $payload,
            'processed_at' => now(),
        ]);
    }
}
