<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Exceptions\InvalidWebhookException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Handles interactions with the Flutterwave API.
 */
class FlutterwaveService
{
    private string $secretKey;

    private string $webhookHash;

    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key', '');
        $this->webhookHash = config('services.flutterwave.webhook_hash', '');
        $this->baseUrl = 'https://api.flutterwave.com/v3';
    }

    /**
     * Verify the incoming webhook signature against our configured hash.
     *
     * @throws InvalidWebhookException
     */
    public function verifyWebhookSignature(?string $signature): void
    {
        if (! $signature || $signature !== $this->webhookHash) {
            Log::warning('Flutterwave webhook signature mismatch.', ['provided' => $signature]);
            throw InvalidWebhookException::signatureMismatch();
        }
    }

    /**
     * Verify a transaction directly with the Flutterwave API.
     * We NEVER trust the webhook payload alone for crediting wallets.
     */
    public function verifyTransaction(string $transactionId): ?array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transactions/{$transactionId}/verify");

        if ($response->successful()) {
            return $response->json('data');
        }

        Log::error('Flutterwave transaction verification failed.', [
            'transaction_id' => $transactionId,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return null;
    }

    /**
     * Initialize a new payment request to get the payment link.
     */
    public function initializePayment(array $payload): ?array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/payments", $payload);

        if ($response->successful()) {
            return $response->json('data');
        }

        Log::error('Flutterwave payment initialization failed.', [
            'payload' => $payload,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return null;
    }

    /**
     * Fetch the list of banks for a given country.
     */
    public function getBanks(string $country = 'NG'): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/banks/{$country}");

        if ($response->successful()) {
            return $response->json('data') ?? [];
        }

        Log::error('Flutterwave get banks failed.', [
            'country' => $country,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return [];
    }

    /**
     * Resolve a bank account number to get the account name.
     */
    public function resolveBankAccount(string $accountNumber, string $bankCode): ?array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/accounts/resolve", [
                'account_number' => $accountNumber,
                'account_bank' => $bankCode,
            ]);

        if ($response->successful()) {
            return $response->json('data');
        }

        return null;
    }
}
