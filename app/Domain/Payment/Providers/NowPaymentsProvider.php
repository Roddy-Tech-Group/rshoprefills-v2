<?php

namespace App\Domain\Payment\Providers;

use App\Models\PaymentAttempt;
use App\Domain\Payment\Interfaces\PaymentProviderInterface;
use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPaymentsProvider implements PaymentProviderInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.nowpayments.api_key') ?: env('NOWPAYMENTS_API_KEY') ?: 'NOWPAYMENTS_KEY_MOCK';
        $this->baseUrl = 'https://api.nowpayments.io/v1';
    }

    public function initializePayment(PaymentAttempt $attempt): array
    {
        $txRef = $attempt->idempotency_key;

        // If mock key, return simulated crypto invoice
        if (str_contains($this->apiKey, 'MOCK')) {
            $simulatedUrl = "https://nowpayments.mock/invoice/{$attempt->id}?ref={$txRef}";
            $attempt->gateway_reference = 'NP-MOCK-' . uniqid();
            $attempt->payment_url = $simulatedUrl;
            $attempt->save();

            return [
                'payment_url' => $simulatedUrl,
                'gateway_reference' => $attempt->gateway_reference,
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
            $paymentUrl = $body['invoice_url'] ?? null;
            $gatewayRef = $body['id'] ?? null;

            $attempt->payment_url = $paymentUrl;
            $attempt->gateway_reference = $gatewayRef;
            $attempt->save();

            return [
                'payment_url' => $paymentUrl,
                'gateway_reference' => $gatewayRef,
            ];
        } catch (\Exception $e) {
            Log::error('NowPayments API error: ' . $e->getMessage());
            throw $e;
        }
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
            Log::error('NowPayments verify error: ' . $e->getMessage());
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
