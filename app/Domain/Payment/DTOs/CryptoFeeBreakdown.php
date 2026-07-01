<?php

namespace App\Domain\Payment\DTOs;

/**
 * Immutable value object representing the crypto fee breakdown shown to the
 * customer at checkout. All monetary amounts are in USD unless otherwise noted.
 *
 * The product price already includes RshopRefills' platform markup (applied by
 * the PricingRule system). This DTO only adds the blockchain-level costs that
 * NOWPayments / the network impose.
 */
class CryptoFeeBreakdown
{
    public function __construct(
        /** The product price in USD (already includes platform markup). */
        public readonly float $productPriceUsd,

        /** Estimated blockchain network fee in USD. */
        public readonly float $networkFeeEstimateUsd,

        /** NOWPayments service fee in USD (typically 0.5%). */
        public readonly float $serviceFeeEstimateUsd,

        /** Total the customer is expected to pay in USD. */
        public readonly float $totalDueUsd,

        /** The cryptocurrency the customer selected (e.g. 'usdttrc20'). */
        public readonly string $payCurrency,

        /** Resolved blockchain network name (e.g. 'tron'). */
        public readonly string $network,

        /** Estimated total in the selected cryptocurrency. */
        public readonly float $estimatedPayAmount,

        /** USD-to-crypto exchange rate used for the estimate. */
        public readonly float $exchangeRate,
    ) {}

    /**
     * Serialise for the frontend fee-estimate API response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_price_usd' => round($this->productPriceUsd, 2),
            'network_fee_usd' => round($this->networkFeeEstimateUsd, 2),
            'service_fee_usd' => round($this->serviceFeeEstimateUsd, 2),
            'total_due_usd' => round($this->totalDueUsd, 2),
            'product_price_crypto' => round($this->productPriceUsd * $this->exchangeRate, 6),
            'network_fee_crypto' => round($this->networkFeeEstimateUsd * $this->exchangeRate, 6),
            'service_fee_crypto' => round($this->serviceFeeEstimateUsd * $this->exchangeRate, 6),
            'pay_currency' => $this->payCurrency,
            'network' => $this->network,
            'estimated_pay_amount' => $this->estimatedPayAmount,
            'exchange_rate' => $this->exchangeRate,
            'disclaimer' => 'Estimated fees. Final amount is calculated at invoice creation and may vary with network conditions.',
        ];
    }
}
