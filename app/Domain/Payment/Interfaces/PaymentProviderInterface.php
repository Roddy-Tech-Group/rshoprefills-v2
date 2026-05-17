<?php

namespace App\Domain\Payment\Interfaces;

use App\Models\PaymentAttempt;

interface PaymentProviderInterface
{
    /**
     * Initialize the payment attempt (e.g. generate hosted URL, crypto invoice, or locks).
     * Returns an array containing payment_url and/or gateway reference details.
     */
    public function initializePayment(PaymentAttempt $attempt): array;

    /**
     * Verify a payment attempt's status directly from the gateway API.
     */
    public function verifyPayment(PaymentAttempt $attempt): bool;

    /**
     * Refund a previously captured payment.
     */
    public function refundPayment(PaymentAttempt $attempt, float $amount): bool;

    /**
     * Normalize incoming webhook payload into a standardized structure.
     */
    public function normalizeWebhook(array $payload): array;
}
