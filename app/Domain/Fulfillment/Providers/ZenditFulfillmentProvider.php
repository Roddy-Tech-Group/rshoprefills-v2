<?php

namespace App\Domain\Fulfillment\Providers;

use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Fulfillment\Interfaces\FulfillmentProviderInterface;
use App\Domain\Shared\Services\CircuitBreaker;
use App\Models\FulfillmentLog;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenditFulfillmentProvider implements FulfillmentProviderInterface
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.zendit.api_key') ?: env('ZENDIT_API_KEY') ?: 'ZENDIT_API_KEY_MOCK';

        // Explicit base URL from config/env always wins.
        $explicitUrl = config('services.zendit.base_url') ?: env('ZENDIT_BASE_URL');
        if ($explicitUrl) {
            $this->baseUrl = $explicitUrl;
        } else {
            $this->baseUrl = 'https://api.zendit.io/v1';
        }
    }

    public function fulfill(OrderItem $item): array
    {
        $offerId = $item->provider_offer_id;

        $title = strtolower($item->product_snapshot['name'] ?? '');
        $isEsim = ($item->product_snapshot['category']['slug'] ?? '') === 'esims' || strpos($title, 'esim') !== false;

        $email = 'dev@roddytechgroup.com';
        $firstName = 'Rshop';
        $lastName = 'Refills';

        $customTransactionId = 'RSR-'.str_replace('-', '', (string) $item->id);

        if ($isEsim) {
            $requestPayload = [
                'offerId' => $offerId,
                'transactionId' => $customTransactionId,
            ];
            $endpoint = '/esim/purchases';
        } else {
            $requestPayload = [
                'offerId' => $offerId,
                'transactionId' => $customTransactionId,
                'fields' => [
                    ['key' => 'recipient.email', 'value' => $email],
                    ['key' => 'recipient.firstName', 'value' => $firstName],
                    ['key' => 'recipient.lastName', 'value' => $lastName],
                ],
            ];

            // For variable price items, Zendit requires value in minor units
            if (isset($item->variant_snapshot['is_variable']) && $item->variant_snapshot['is_variable']) {
                $divisor = (float) ($item->variant_snapshot['metadata']['send']['currencyDivisor'] ?? 100);
                $requestPayload['value'] = [
                    'type' => 'PRICE',
                    'value' => (int) round($item->display_amount * $divisor),
                ];
            }
            $endpoint = '/vouchers/purchases';
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
                'reference' => $responsePayload['transactionId'] ?? 'MOCK-TX-'.uniqid(),
                'payload' => $responsePayload,
            ];
        }

        $breaker = new CircuitBreaker('zendit_api', 10, 5);
        if ($breaker->isOpen()) {
            Log::warning("Zendit API circuit breaker is OPEN. Failing gracefully for item {$item->id}");

            return [
                'status' => FulfillmentStatus::Failed,
                'reference' => null,
                'payload' => ['error' => 'Zendit API circuit breaker is open due to recent failures. Please try again later.'],
            ];
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post("{$this->baseUrl}{$endpoint}", $requestPayload);

            $responseBody = $response->json() ?? [];

            FulfillmentLog::create([
                'order_item_id' => $item->id,
                'provider_name' => 'zendit',
                'request_payload' => $requestPayload,
                'response_payload' => $responseBody,
                'status' => $response->successful() ? 'SUCCESS' : 'FAILED',
                'error_message' => $response->successful() ? null : $response->body(),
            ]);

            if ($response->failed() || ! isset($responseBody['transactionId'])) {
                $breaker->recordFailure();
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
                'SUCCESS', 'COMPLETED', 'DONE' => FulfillmentStatus::Fulfilled,
                'PENDING', 'PROCESSING', 'AUTHORIZED', 'IN_PROGRESS', 'ACCEPTED' => FulfillmentStatus::Processing,
                default => FulfillmentStatus::Failed,
            };

            $breaker->recordSuccess();

            return [
                'status' => $statusEnum,
                // In v1, they poll using Zendit's returned transactionId!
                'reference' => $responseBody['transactionId'],
                'payload' => $responseBody,
            ];
        } catch (\Exception $e) {
            $breaker->recordFailure();

            Log::error('Zendit fulfillment exception: '.$e->getMessage());

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
            $zenditTxId = $item->fulfillment_reference;
            if (! $zenditTxId) {
                Log::warning("Zendit verifyStatus: no fulfillment_reference on item {$item->id}");

                return ['status' => FulfillmentStatus::Failed, 'payload' => []];
            }

            $title = strtolower($item->product_snapshot['name'] ?? '');
            $isEsim = ($item->product_snapshot['category']['slug'] ?? '') === 'esims' || strpos($title, 'esim') !== false;
            $endpoint = $isEsim ? "/esim/purchases/{$zenditTxId}" : "/vouchers/purchases/{$zenditTxId}";

            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->get("{$this->baseUrl}{$endpoint}");

            $responseBody = $response->json() ?? [];

            if ($response->failed()) {
                Log::error("Zendit verifyStatus failed for {$zenditTxId}", [
                    'http_status' => $response->status(),
                    'body' => $responseBody,
                ]);

                return [
                    'status' => FulfillmentStatus::Processing,
                    'payload' => $responseBody,
                ];
            }

            $txStatus = $responseBody['status'] ?? 'PENDING';
            $statusEnum = match (strtoupper($txStatus)) {
                'SUCCESS', 'COMPLETED', 'DONE' => FulfillmentStatus::Fulfilled,
                'PENDING', 'PROCESSING', 'AUTHORIZED', 'IN_PROGRESS', 'ACCEPTED' => FulfillmentStatus::Processing,
                default => FulfillmentStatus::Failed,
            };

            return [
                'status' => $statusEnum,
                'payload' => $responseBody,
            ];
        } catch (\Exception $e) {
            Log::error('Zendit verifyStatus exception: '.$e->getMessage());

            return ['status' => FulfillmentStatus::Processing, 'payload' => []];
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
        $items = $rawPayload['items'] ?? [];
        $pins = [];
        $flat = [];

        foreach ($items as $v) {
            $pins[] = [
                'code' => $v['code'] ?? null,
                'pin' => $v['pin'] ?? null,
                'serialNumber' => $v['serialNumber'] ?? null,
                'instructions' => $v['redemptionInstructions'] ?? null,
                'redemption_url' => $v['redemptionUrl'] ?? null,
            ];
        }

        $receipt = $rawPayload['receipt'] ?? null;
        if ($receipt) {
            if (! empty($receipt['voucherId']) && empty($pins)) {
                $pins[] = ['pin' => $receipt['voucherId']];
            }
            if (! empty($receipt['redemptionUrl'])) {
                $flat['redemption_url'] = $receipt['redemptionUrl'];
            }
            if (! empty($receipt['epin'])) {
                $flat['epin'] = $receipt['epin'];
            }
            if (! empty($receipt['instructions'])) {
                $flat['instructions'] = $receipt['instructions'];
            }
        }

        if (! empty($pins)) {
            $firstPin = $pins[0];
            if (! empty($firstPin['code'])) {
                $flat['code'] = $firstPin['code'];
            }
            if (! empty($firstPin['pin'])) {
                $flat['pin'] = $firstPin['pin'];
            }
            if (! empty($firstPin['serialNumber'])) {
                $flat['serial_number'] = $firstPin['serialNumber'];
            }
            if (! empty($firstPin['instructions'])) {
                $flat['instructions'] = $flat['instructions'] ?? $firstPin['instructions'];
            }
            if (! empty($firstPin['redemption_url'])) {
                $flat['redemption_url'] = $flat['redemption_url'] ?? $firstPin['redemption_url'];
            }
        }

        $esimData = null;
        $confirmation = $rawPayload['confirmation'] ?? null;
        if ($confirmation && ! empty($confirmation['iccid'])) {
            $esimData = [
                'iccid' => $confirmation['iccid'] ?? null,
                'lpaUrl' => $confirmation['smdpAddress'] ?? null,
                'manualActivationCode' => $confirmation['activationCode'] ?? null,
            ];

            $flat['esim_iccid'] = $esimData['iccid'];
            $flat['esim_lpa'] = $esimData['lpaUrl'];
            $flat['esim_activation_code'] = $esimData['manualActivationCode'];
        }

        return array_merge($flat, [
            'pins' => $pins,
            'esim' => $esimData,
        ]);
    }

    private function getMockResponse(OrderItem $item, string $customIdentifier): array
    {
        $isEsim = isset($item->product_snapshot['category']['slug']) && $item->product_snapshot['category']['slug'] === 'esims';

        $response = [
            'transactionId' => 'ZND-MOCK-'.uniqid(),
            'customIdentifier' => $customIdentifier,
            'status' => 'SUCCESS',
            'offerId' => $item->provider_offer_id,
            'created' => now()->toIso8601String(),
        ];

        if ($isEsim) {
            $response['esim'] = [
                'iccid' => '89852'.str_pad((string) rand(1, 9999999999999), 13, '0', STR_PAD_LEFT),
                'lpaUrl' => 'rsp.zendit.io',
                'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=LPA:1$rsp.zendit.io$MOCK-ACTIVATION-CODE',
                'manualActivationCode' => 'LPA:1$rsp.zendit.io$MOCK-ACTIVATION-CODE',
            ];
        } else {
            $response['vouchers'] = [
                [
                    'code' => strtoupper(uniqid('MOCK-CODE-')),
                    'pin' => str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT),
                    'serialNumber' => 'SN-'.rand(100000, 999999),
                    'redemptionInstructions' => 'Go to brand website and enter mock code during check out.',
                ],
            ];
        }

        return $response;
    }
}
