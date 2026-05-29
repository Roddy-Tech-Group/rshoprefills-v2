<?php

namespace App\Domain\Fulfillment\Interfaces;

use App\Models\OrderItem;

interface FulfillmentProviderInterface
{
    /**
     * Dispatch order item purchase transaction to the provider.
     * Returns an array containing status and provider reference details.
     */
    public function fulfill(OrderItem $item): array;

    /**
     * Poll the status of a pending transaction from the provider.
     */
    public function verifyStatus(OrderItem $item): array;

    /**
     * Cancel / Refund a failed transaction.
     */
    public function refund(OrderItem $item): bool;

    /**
     * Normalize incoming provider response payload.
     */
    public function normalizeResponse(array $rawPayload): array;
}
