<?php

namespace App\Domain\Payment\Providers;

use App\Models\PaymentAttempt;
use App\Domain\Payment\Interfaces\PaymentProviderInterface;
use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwavePaymentProvider implements PaymentProviderInterface
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key') ?: env('FLUTTERWAVE_SECRET_KEY') ?: 'FLW_SECRET_KEY_MOCK';
        $this->baseUrl = 'https://api.flutterwave.com/v3';
    }

    public function initializePayment(PaymentAttempt $attempt): array
    {
        $txRef = $attempt->idempotency_key;
        $payable = $attempt->payable;

        $title = 'RshopRefills Order Refill';
        $description = $attempt->order ? "Order #{$attempt->order->order_number}" : "Payment Ref #{$txRef}";

        if ($payable instanceof \App\Models\WalletFunding) {
            $title = 'RshopRefills Wallet Deposit';
            $description = "Wallet Funding Ref #{$payable->reference}";
        }

        $publicKey = config('services.flutterwave.public_key') ?: env('FLUTTERWAVE_PUBLIC_KEY') ?: 'FLW_PUB_KEY_MOCK';

        // Set attempt's gateway reference to tx_ref for tracking before webhook or capture
        $attempt->gateway_reference = $txRef;
        $attempt->payment_url = null;
        $attempt->save();

        return [
            'provider' => 'flutterwave',
            'mode' => 'inline',
            'tx_ref' => $txRef,
            'public_key' => $publicKey,
            'amount' => (float)$attempt->amount,
            'currency' => strtoupper($attempt->currency),
            'customer' => [
                'email' => $attempt->user->email,
                'name' => $attempt->user->name,
            ],
            'customizations' => [
                'title' => $title,
                'description' => $description,
            ],
            'gateway_reference' => $txRef,
        ];
    }

    public function verifyPayment(PaymentAttempt $attempt): bool
    {
        if (str_contains($this->secretKey, 'MOCK')) {
            $attempt->payment_status = PaymentStatus::Paid;
            $attempt->confirmed_at = now();
            $attempt->save();
            return true;
        }

        try {
            $txId = $attempt->gateway_reference;
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/transactions/{$txId}/verify");

            if ($response->failed()) {
                Log::error("Flutterwave transaction verification failed: {$txId}");
                return false;
            }

            $body = $response->json();
            $status = $body['data']['status'] ?? null;
            $amount = $body['data']['amount'] ?? 0;
            $currency = $body['data']['currency'] ?? '';

            if ($status === 'successful' && (float)$amount >= (float)$attempt->amount && strtoupper($currency) === strtoupper($attempt->currency)) {
                $attempt->payment_status = PaymentStatus::Paid;
                $attempt->confirmed_at = now();
                $attempt->verification_payload = $body;
                $attempt->save();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Flutterwave verification error: ' . $e->getMessage());
            return false;
        }
    }

    public function refundPayment(PaymentAttempt $attempt, float $amount): bool
    {
        if (str_contains($this->secretKey, 'MOCK')) {
            $attempt->payment_status = PaymentStatus::Refunded;
            $attempt->save();
            return true;
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/refunds", [
                    'id' => $attempt->gateway_reference,
                    'amount' => $amount,
                ]);

            if ($response->failed()) {
                Log::error('Flutterwave refund failed', ['body' => $response->json()]);
                return false;
            }

            $attempt->payment_status = PaymentStatus::Refunded;
            $attempt->save();
            return true;
        } catch (\Exception $e) {
            Log::error('Flutterwave refund error: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyByReference(string $txRef): ?array
    {
        if (str_contains($this->secretKey, 'MOCK')) {
            return [
                'status' => 'successful',
                'amount' => 100.0,
                'currency' => 'USD',
                'transaction_id' => 'FLW-MOCK-' . uniqid(),
                'tx_ref' => $txRef,
                'raw' => ['simulated' => true]
            ];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/transactions/verify_by_reference", [
                    'tx_ref' => $txRef
                ]);

            if ($response->failed()) {
                Log::error("Flutterwave transaction reference verification failed for: {$txRef}", [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return null;
            }

            $body = $response->json();
            $data = $body['data'] ?? null;

            if ($data) {
                return [
                    'status' => $data['status'] ?? null,
                    'amount' => $data['amount'] ?? 0.0,
                    'currency' => $data['currency'] ?? '',
                    'transaction_id' => $data['id'] ?? null,
                    'tx_ref' => $data['tx_ref'] ?? null,
                    'raw' => $body
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Flutterwave verifyByReference error: ' . $e->getMessage());
            return null;
        }
    }

    public function normalizeWebhook(array $payload): array
    {
        return [
            'status' => $payload['status'] ?? ($payload['data']['status'] ?? null),
            'amount' => $payload['amount'] ?? ($payload['data']['amount'] ?? 0),
            'currency' => $payload['currency'] ?? ($payload['data']['currency'] ?? 'USD'),
            'transaction_id' => $payload['id'] ?? ($payload['data']['id'] ?? null),
            'reference' => $payload['tx_ref'] ?? ($payload['data']['tx_ref'] ?? null),
        ];
    }
}
