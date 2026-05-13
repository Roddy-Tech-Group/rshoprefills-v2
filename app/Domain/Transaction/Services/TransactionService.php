<?php

namespace App\Domain\Transaction\Services;

use App\Domain\Shared\Enums\Currency;
use App\Domain\Transaction\Exceptions\DuplicateTransactionException;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;

/**
 * Handles the creation of immutable ledger entries and idempotency guarantees.
 */
class TransactionService
{
    /**
     * Generate a globally unique, human-readable reference.
     * Example: TXN-NGN-20260514-AB12CD
     */
    public function generateReference(string $prefix, Currency $currency): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$currency->value}-{$date}-{$random}";
    }

    /**
     * Ensure a transaction with this idempotency key hasn't been processed yet.
     *
     * @throws DuplicateTransactionException
     */
    public function checkIdempotency(string $idempotencyKey): void
    {
        if (WalletTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
            throw DuplicateTransactionException::forIdempotencyKey($idempotencyKey);
        }
    }

    /**
     * Create an immutable ledger entry for a wallet movement.
     * This method assumes you are already inside a database transaction with a locked wallet.
     */
    public function recordTransaction(array $data): WalletTransaction
    {
        if (isset($data['idempotency_key'])) {
            $this->checkIdempotency($data['idempotency_key']);
        }

        // We create the record. Note that we do not allow updating WalletTransactions anywhere in the codebase.
        return WalletTransaction::create($data);
    }
}
