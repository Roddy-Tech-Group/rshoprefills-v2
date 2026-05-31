<?php

namespace App\Domain\Wallet\Services;

use App\Domain\Payment\Providers\FlutterwavePaymentProvider;
use App\Domain\Payment\Services\PaymentGatewayFactory;
use App\Models\WalletFunding;
use Illuminate\Support\Facades\Log;

class FundingVerificationService
{
    public function __construct(
        private readonly PaymentGatewayFactory $paymentGatewayFactory
    ) {}

    /**
     * Verify a wallet funding transaction server-to-server and check for tampering.
     */
    public function verify(WalletFunding $funding): bool
    {
        $providerName = $funding->gateway;

        try {
            $provider = $this->paymentGatewayFactory->getProvider($providerName);
        } catch (\InvalidArgumentException $e) {
            Log::error('Unsupported payment provider resolved for wallet funding.', [
                'reference' => $funding->reference,
                'gateway' => $providerName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if ($provider instanceof FlutterwavePaymentProvider) {
            $verification = $provider->verifyByReference($funding->reference);

            if (! $verification) {
                Log::warning('Flutterwave verification failed - no transaction data returned', [
                    'reference' => $funding->reference,
                ]);

                return false;
            }

            // Extract and compare response attributes to prevent tampering
            $flwStatus = $verification['status'] ?? null;
            $flwAmount = (float) ($verification['amount'] ?? 0.0);
            $flwCurrency = strtoupper($verification['currency'] ?? '');
            $flwRef = $verification['tx_ref'] ?? null;

            // 1. Status Check
            if ($flwStatus !== 'successful' && $flwStatus !== 'success') {
                Log::warning('Flutterwave verification failed - status is not successful', [
                    'reference' => $funding->reference,
                    'status' => $flwStatus,
                ]);

                return false;
            }

            // 2. Tamper Prevention: Amount check
            $expectedAmount = (float) $funding->amount;
            if ($flwAmount < $expectedAmount) {
                Log::error('TAMPER DETECTED: Flutterwave verified amount is lower than requested', [
                    'reference' => $funding->reference,
                    'expected' => $expectedAmount,
                    'received' => $flwAmount,
                ]);

                return false;
            }

            // 3. Tamper Prevention: Currency check
            $expectedCurrency = strtoupper($funding->currency->value);
            if ($expectedCurrency !== $flwCurrency) {
                Log::error('TAMPER DETECTED: Flutterwave verified currency mismatch', [
                    'reference' => $funding->reference,
                    'expected' => $expectedCurrency,
                    'received' => $flwCurrency,
                ]);

                return false;
            }

            // Update funding properties on success
            $funding->gateway_reference = $verification['transaction_id'];
            $funding->verification_payload_snapshot = $verification['raw'] ?? $verification;
            $funding->verified_at = now();
            $funding->save();

            return true;
        }

        Log::error('Verification not implemented for provider', [
            'reference' => $funding->reference,
            'gateway' => $providerName,
        ]);

        return false;
    }
}
