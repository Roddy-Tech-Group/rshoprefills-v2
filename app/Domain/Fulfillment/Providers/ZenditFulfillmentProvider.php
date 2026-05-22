<?php

namespace App\Domain\Fulfillment\Providers;

use App\Models\OrderItem;
use App\Domain\Fulfillment\Interfaces\FulfillmentProviderInterface;
use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Models\FulfillmentLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenditFulfillmentProvider implements FulfillmentProviderInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.zendit.api_key') ?: env('ZENDIT_API_KEY') ?: 'ZENDIT_API_KEY_MOCK';

        // Explicit base URL from config/env always wins. Otherwise, auto-detect
        // from the API key prefix: sandbox keys start with "sand_".
        $explicitUrl = config('services.zendit.base_url') ?: env('ZENDIT_BASE_URL');
        if ($explicitUrl) {
            $this->baseUrl = $explicitUrl;
        } elseif (str_starts_with($this->apiKey, 'sand_')) {
            $this->baseUrl = 'https://api.sandbox.zendit.io/v1';
        } else {
            $this->baseUrl = 'https://api.zendit.io/v1';
        }
    }

    public function fulfill(OrderItem $item): array
    {
        $offerId = $item->provider_offer_id;

        $email = 'dev@roddytechgroup.com';
        $firstName = 'Rshop';
        $lastName = 'Refills';

        $transactionId = 'RSR-' . substr(str_replace('-', '', (string) $item->id), 0, 16);

        $requestPayload = [
            'offerId' => $offerId,
            'transactionId' => $transactionId,
            'fields' => [
                ['key' => 'recipient.email', 'value' => $email],
                ['key' => 'recipient.firstName', 'value' => $firstName],
                ['key' => 'recipient.lastName', 'value' => $lastName]
            ]
        ];

        // For variable price items, Zendit requires sendAmount in minor units
        if (isset($item->variant_snapshot['is_variable']) && $item->variant_snapshot['is_variable']) {
            $divisor = (float)($item->variant_snapshot['metadata']['send']['currencyDivisor'] ?? 100);
            // Display amount represents selected face value
            $requestPayload['sendAmount'] = (int)($item->display_amount * $divisor);
        }

        // Mock mode
        if (str_contains($this->apiKey, 'MOCK')) {
            $responsePayload = $this->getMockResponse($item, $requestPayload['transactionId']);
            
            FulfillmentLog::create([
                'order_item_id' => $item->id,
                'provider_name' => 'zendit',
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'status' => 'SUCCESS',
            ]);

            return [
                'status' => FulfillmentStatus::Fulfilled,
                'reference' => $responsePayload['transactionId'] ?? 'MOCK-TX-' . uniqid(),
                'payload' => $responsePayload,
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->post("{$this->baseUrl}/vouchers/purchases", $requestPayload);

            $responseBody = $response->json() ?? [];

            FulfillmentLog::create([
                'order_item_id' => $item->id,
                'provider_name' => 'zendit',
                'request_payload' => $requestPayload,
                'response_payload' => $responseBody,
                'status' => $response->successful() ? 'SUCCESS' : 'FAILED',
                'error_message' => $response->successful() ? null : $response->body(),
            ]);

            if ($response->failed()) {
                Log::error("Zendit transaction failed: {$item->id}", [
                    'status' => $response->status(),
                    'body' => $responseBody,
                ]);

                return [
                    'status' => FulfillmentStatus::Failed,
                    'reference' => null,
                    'payload' => $responseBody,
                ];
            }

            $txStatus = $responseBody['status'] ?? 'PENDING';
            $statusEnum = match (strtoupper($txStatus)) {
                'SUCCESS', 'COMPLETED' => FulfillmentStatus::Fulfilled,
                'PENDING', 'PROCESSING', 'ACCEPTED' => FulfillmentStatus::Processing,
                default => FulfillmentStatus::Failed,
            };

            return [
                'status' => $statusEnum,
                'reference' => $responseBody['transactionId'] ?? null,
                'payload' => $responseBody,
            ];
        } catch (\Exception $e) {
            Log::error("Zendit fulfillment exception: " . $e->getMessage());
            
            FulfillmentLog::create([
                'order_item_id' => $item->id,
                'provider_name' => 'zendit',
                'request_payload' => $requestPayload,
                'response_payload' => null,
                'status' => 'EXCEPTION',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'status' => FulfillmentStatus::Failed,
                'reference' => null,
                'payload' => [],
            ];
        }
    }

    public function verifyStatus(OrderItem $item): array
    {
        if (str_contains($this->apiKey, 'MOCK')) {
            return [
                'status' => FulfillmentStatus::Fulfilled,
                'payload' => $item->fulfillment_payload ?? [],
            ];
        }

        try {
            $txId = $item->fulfillment_reference;
            if (!$txId) {
                return ['status' => FulfillmentStatus::Failed, 'payload' => []];
            }

            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->get("{$this->baseUrl}/transactions/{$txId}");

            $responseBody = $response->json() ?? [];

            if ($response->failed()) {
                return [
                    'status' => FulfillmentStatus::Failed,
                    'payload' => $responseBody,
                ];
            }

            $txStatus = $responseBody['status'] ?? 'PENDING';
            $statusEnum = match (strtoupper($txStatus)) {
                'SUCCESS', 'COMPLETED' => FulfillmentStatus::Fulfilled,
                'PENDING', 'PROCESSING', 'ACCEPTED' => FulfillmentStatus::Processing,
                default => FulfillmentStatus::Failed,
            };

            return [
                'status' => $statusEnum,
                'payload' => $responseBody,
            ];
        } catch (\Exception $e) {
            Log::error("Zendit verifyStatus exception: " . $e->getMessage());
            return ['status' => FulfillmentStatus::Failed, 'payload' => []];
        }
    }

    public function refund(OrderItem $item): bool
    {
        // Zendit handles transactions instantly. Reversals are usually manual or automated by Zendit upon transaction timeouts.
        return true;
    }

    public function normalizeResponse(array $rawPayload): array
    {
        // Normalize response to extract code/pin/eSIM QR
        $vouchers = $rawPayload['vouchers'] ?? [];
        $pins = [];
        foreach ($vouchers as $v) {
            $pins[] = [
                'code' => $v['code'] ?? null,
                'pin' => $v['pin'] ?? null,
                'serialNumber' => $v['serialNumber'] ?? null,
                'instructions' => $v['redemptionInstructions'] ?? null,
            ];
        }

        $esim = $rawPayload['esim'] ?? null;

        return [
            'pins' => $pins,
            'esim' => $esim ? [
                'iccid' => $esim['iccid'] ?? null,
                'lpaUrl' => $esim['lpaUrl'] ?? null,
                'qrCodeUrl' => $esim['qrCodeUrl'] ?? null,
                'manualActivationCode' => $esim['manualActivationCode'] ?? null,
            ] : null,
        ];
    }

    private function getMockResponse(OrderItem $item, string $customIdentifier): array
    {
        $isEsim = isset($item->product_snapshot['category']['slug']) && $item->product_snapshot['category']['slug'] === 'esims';
        
        $response = [
            'transactionId' => 'ZND-MOCK-' . uniqid(),
            'customIdentifier' => $customIdentifier,
            'status' => 'SUCCESS',
            'offerId' => $item->provider_offer_id,
            'created' => now()->toIso8601String(),
        ];

        if ($isEsim) {
            $response['esim'] = [
                'iccid' => '89852' . str_pad((string)rand(1, 9999999999999), 13, '0', STR_PAD_LEFT),
                'lpaUrl' => 'rsp.zendit.io',
                'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=LPA:1$rsp.zendit.io$MOCK-ACTIVATION-CODE',
                'manualActivationCode' => 'LPA:1$rsp.zendit.io$MOCK-ACTIVATION-CODE',
            ];
        } else {
            $response['vouchers'] = [
                [
                    'code' => strtoupper(uniqid('MOCK-CODE-')),
                    'pin' => str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT),
                    'serialNumber' => 'SN-' . rand(100000, 999999),
                    'redemptionInstructions' => 'Go to brand website and enter mock code during check out.',
                ]
            ];
        }

        return $response;
    }
}
