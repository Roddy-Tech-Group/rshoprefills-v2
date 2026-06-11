<?php

namespace App\Domain\Payment\Support;

/**
 * Computes the Flutterwave processing fee for disclosure to the customer.
 *
 * The customer already pays Flutterwave's "Transaction Fee" + "International
 * Processing Fee" on top of the order amount; we surface them as one combined
 * "Processing fee" so checkout shows the real total. VAT (7.5% of the fees) is
 * merchant-paid, so it is reported separately for our own accounting and never
 * added to what the customer pays.
 *
 * Rates come from config/payment_fees.php (an offline estimate); Flutterwave's
 * /v3/transactions/fee endpoint is the live source of truth, and the actual fee
 * charged is read back from the gateway response for the receipt.
 */
final class ProcessingFee
{
    /**
     * @return array{
     *     transaction_fee: float,
     *     international_fee: float,
     *     processing_fee: float,
     *     vat: float,
     *     amount: float,
     *     customer_total: float,
     *     settlement: float,
     * }
     */
    public static function for(float $amount, string $method, bool $international = false): array
    {
        $method = strtolower(trim($method));

        if ($amount <= 0 || in_array($method, (array) config('payment_fees.fee_free_methods', []), true)) {
            return self::zero($amount);
        }

        $rates = config("payment_fees.methods.{$method}")
            ?? config('payment_fees.default', ['transaction' => 0.0, 'international' => 0.0]);

        $transactionFee = self::round($amount * ((float) $rates['transaction'] / 100));
        $internationalFee = $international
            ? self::round($amount * ((float) $rates['international'] / 100))
            : 0.0;

        $processingFee = self::round($transactionFee + $internationalFee);
        $vat = self::round($processingFee * ((float) config('payment_fees.vat_percent', 7.5) / 100));

        return [
            'transaction_fee' => $transactionFee,
            'international_fee' => $internationalFee,
            // What the customer pays on top of the amount (the two fees combined).
            'processing_fee' => $processingFee,
            // Merchant-paid tax on the fees - never shown to the customer.
            'vat' => $vat,
            'amount' => self::round($amount),
            'customer_total' => self::round($amount + $processingFee),
            'settlement' => self::round($amount - $vat),
        ];
    }

    /**
     * @return array{transaction_fee: float, international_fee: float, processing_fee: float, vat: float, amount: float, customer_total: float, settlement: float}
     */
    private static function zero(float $amount): array
    {
        $amount = self::round(max(0.0, $amount));

        return [
            'transaction_fee' => 0.0,
            'international_fee' => 0.0,
            'processing_fee' => 0.0,
            'vat' => 0.0,
            'amount' => $amount,
            'customer_total' => $amount,
            'settlement' => $amount,
        ];
    }

    private static function round(float $value): float
    {
        return round($value, 2);
    }
}
