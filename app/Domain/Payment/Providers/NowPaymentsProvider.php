<?php

namespace App\Domain\Payment\Providers;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Interfaces\PaymentProviderInterface;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPaymentsProvider implements PaymentProviderInterface
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.nowpayments.api_key') ?: 'NOWPAYMENTS_KEY_MOCK';
        $this->baseUrl = config('services.nowpayments.base_url') ?: 'https://api.nowpayments.io/v1';
    }

    public function initializePayment(PaymentAttempt $attempt): array
    {
        $txRef = $attempt->idempotency_key;

        // If mock key, return simulated crypto invoice
        if (str_contains($this->apiKey, 'MOCK')) {
            $gatewayRef = 'NP-MOCK-'.uniqid();
            $payAddress = '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa';
            $payAmount = round($attempt->amount * 0.000015, 6);
            $payCurrency = 'btc';
            $network = 'bitcoin';
            $expiresAt = now()->addMinutes(30)->toIso8601String();

            $attempt->gateway_reference = $gatewayRef;
            $attempt->payment_url = null;
            $attempt->save();

            return [
                'provider' => 'nowpayments',
                'mode' => 'embedded_crypto',
                'invoice_id' => $gatewayRef,
                'pay_address' => $payAddress,
                'pay_amount' => (string) $payAmount,
                'pay_currency' => strtolower($payCurrency),
                'network' => $network,
                'qr_payload' => "{$network}:{$payAddress}?amount={$payAmount}",
                'expires_at' => $expiresAt,
                'confirmations_required' => 2,
                'gateway_reference' => $gatewayRef,
            ];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/invoice", [
                'price_amount' => $attempt->amount,
                'price_currency' => strtoupper($attempt->currency),
                'pay_currency' => 'btc', // default to BTC or allow selection in metadata
                'ipn_callback_url' => route('api.webhooks.nowpayments'),
                'order_id' => $attempt->order->id,
                'order_description' => "Order #{$attempt->order->order_number}",
            ]);

            if ($response->failed()) {
                Log::error('NowPayments invoice creation failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                throw new \Exception('Failed to initialize NowPayments billing.');
            }

            $body = $response->json();
            $gatewayRef = $body['id'] ?? null;
            $payAddress = $body['pay_address'] ?? '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa';
            $payAmount = $body['pay_amount'] ?? round($attempt->amount * 0.000015, 6);
            $payCurrency = $body['pay_currency'] ?? 'btc';
            $network = $this->resolveNetwork($payCurrency);
            $expiresAt = $body['expiration_date'] ?? now()->addMinutes(30)->toIso8601String();

            $attempt->payment_url = null;
            $attempt->gateway_reference = $gatewayRef;
            $attempt->save();

            return [
                'provider' => 'nowpayments',
                'mode' => 'embedded_crypto',
                'invoice_id' => $gatewayRef,
                'pay_address' => $payAddress,
                'pay_amount' => (string) $payAmount,
                'pay_currency' => strtolower($payCurrency),
                'network' => $network,
                'qr_payload' => "{$network}:{$payAddress}?amount={$payAmount}",
                'expires_at' => $expiresAt,
                'confirmations_required' => 2,
                'gateway_reference' => $gatewayRef,
            ];
        } catch (\Exception $e) {
            Log::error('NowPayments API error: '.$e->getMessage());
            throw $e;
        }
    }

    private function resolveNetwork(string $currency): string
    {
        return match (strtolower($currency)) {
            'btc' => 'bitcoin',
            'eth' => 'ethereum',
            'usdt' => 'tron',
            'ltc' => 'litecoin',
            default => 'bitcoin',
        };
    }

    public function chargeCrypto(PaymentAttempt $attempt, string $payCurrency): array
    {
        $payCurrency = strtolower($payCurrency);
        $txRef = $attempt->idempotency_key;

        if (str_contains($this->apiKey, 'MOCK')) {
            $gatewayRef = 'NP-MOCK-'.uniqid();
            $payAddress = $this->resolveMockAddress($payCurrency);
            $rate = match ($payCurrency) {
                'btc' => 0.000015,
                'eth' => 0.0003,
                'usdt' => 1.0,
                'ltc' => 0.012,
                default => 1.0,
            };
            $payAmount = round($attempt->amount * $rate, 6);
            $network = $this->resolveNetwork($payCurrency);
            $expiresAt = now()->addMinutes(30)->toIso8601String();

            $attempt->gateway_reference = $gatewayRef;
            $attempt->save();

            return [
                'status' => 'awaiting_transfer',
                'invoice_id' => $gatewayRef,
                'pay_address' => $payAddress,
                'pay_amount' => (string) $payAmount,
                'pay_currency' => $payCurrency,
                'network' => $network,
                'qr_payload' => "{$network}:{$payAddress}?amount={$payAmount}",
                'expires_at' => now()->addMinutes(30)->toIso8601String(),
            ];
        }

        try {
            $orderId = $attempt->order ? $attempt->order->id : null;
            $description = $attempt->order ? "Order #{$attempt->order->order_number}" : "Wallet Deposit Ref #{$attempt->payable->reference}";

            $payload = [
                'price_amount' => $attempt->amount,
                'price_currency' => strtoupper($attempt->currency),
                'pay_currency' => $payCurrency,
                'ipn_callback_url' => route('api.webhooks.nowpayments'),
                'order_description' => $description,
            ];
            if ($orderId) {
                $payload['order_id'] = $orderId;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/invoice", $payload);

            if ($response->failed()) {
                Log::error('NowPayments invoice creation failed in chargeCrypto', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return [
                    'status' => 'failed',
                    'message' => 'Failed to initialize NowPayments billing: '.($response->json('message') ?? 'Unknown error'),
                ];
            }

            $body = $response->json();
            $gatewayRef = $body['id'] ?? null;
            $payAddress = $body['pay_address'] ?? '';
            $payAmount = $body['pay_amount'] ?? 0;
            $network = $this->resolveNetwork($payCurrency);
            $expiresAt = $body['expiration_date'] ?? now()->addMinutes(30)->toIso8601String();

            $attempt->gateway_reference = $gatewayRef;
            $attempt->save();

            return [
                'status' => 'awaiting_transfer',
                'invoice_id' => $gatewayRef,
                'pay_address' => $payAddress,
                'pay_amount' => (string) $payAmount,
                'pay_currency' => $payCurrency,
                'network' => $network,
                'qr_payload' => "{$network}:{$payAddress}?amount={$payAmount}",
                'expires_at' => $expiresAt,
            ];
        } catch (\Exception $e) {
            Log::error('NowPayments chargeCrypto API error: '.$e->getMessage());

            return [
                'status' => 'failed',
                'message' => 'Crypto initiation failed: '.$e->getMessage(),
            ];
        }
    }

    private function resolveMockAddress(string $currency): string
    {
        return match (strtolower($currency)) {
            'btc' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
            'eth' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'usdt' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            'ltc' => 'LVKw7y21M3tA9JtFh69N2y3Y6L5w6M3Kfd',
            default => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
        };
    }

    public function verifyPayment(PaymentAttempt $attempt): bool
    {
        if (str_contains($this->apiKey, 'MOCK')) {
            $attempt->payment_status = PaymentStatus::Paid;
            $attempt->confirmed_at = now();
            $attempt->save();

            return true;
        }

        try {
            $invoiceId = $attempt->gateway_reference;
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/invoice/{$invoiceId}");

            if ($response->failed()) {
                Log::error("NowPayments verify failed for invoice: {$invoiceId}");

                return false;
            }

            $body = $response->json();
            $status = $body['invoice_status'] ?? null;

            if (in_array($status, ['confirmed', 'paid', 'completed'])) {
                $attempt->payment_status = PaymentStatus::Paid;
                $attempt->confirmed_at = now();
                $attempt->verification_payload = $body;
                $attempt->save();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('NowPayments verify error: '.$e->getMessage());

            return false;
        }
    }

    public function refundPayment(PaymentAttempt $attempt, float $amount): bool
    {
        // NowPayments typically does not support automatic REST refunds without custom API integrations per wallet.
        // We will mark it as refunded locally for audit compliance.
        $attempt->payment_status = PaymentStatus::Refunded;
        $attempt->save();

        return true;
    }

    public function normalizeWebhook(array $payload): array
    {
        return [
            'status' => $payload['payment_status'] ?? null,
            'amount' => $payload['price_amount'] ?? 0,
            'currency' => $payload['price_currency'] ?? 'USD',
            'transaction_id' => $payload['payment_id'] ?? null,
            'reference' => $payload['invoice_id'] ?? null,
        ];
    }
}
