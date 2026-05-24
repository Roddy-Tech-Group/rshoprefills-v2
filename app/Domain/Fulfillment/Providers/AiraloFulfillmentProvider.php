<?php

namespace App\Domain\Fulfillment\Providers;

use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use App\Domain\Fulfillment\Interfaces\FulfillmentProviderInterface;
use App\Models\FulfillmentLog;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiraloFulfillmentProvider implements FulfillmentProviderInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.airalo.base_url', 'https://partners-api.airalo.com/v2');
        $this->clientId = (string) config('services.airalo.client_id', '');
        $this->clientSecret = (string) config('services.airalo.client_secret', '');
    }

    private function getAccessToken(): string
    {
        return Cache::remember('airalo_access_token', now()->addMinutes(45), function () {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->asForm()
                ->post("{$this->baseUrl}/token", [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to authenticate with Airalo API');
            }

            return $response->json('data.access_token') ?? $response->json('access_token');
        });
    }

    public function fulfill(OrderItem $item): array
    {
        // provider_offer_id was saved as "airalo_{id}" by the normalizer, so strip prefix
        $packageId = str_replace('airalo_', '', $item->provider_offer_id);

        $requestPayload = [
            'quantity' => $item->quantity,
            'package_id' => $packageId,
            'description' => "Order {$item->order->order_number} - Item {$item->id}",
        ];

        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withToken($this->getAccessToken())
                ->acceptJson()
                ->post("{$this->baseUrl}/orders", $requestPayload);

            $responseBody = $response->json() ?? [];

            FulfillmentLog::create([
                'order_item_id' => $item->id,
                'provider_name' => 'airalo',
                'request_payload' => $requestPayload,
                'response_payload' => $responseBody,
                'status' => $response->successful() ? 'SUCCESS' : 'FAILED',
                'error_message' => $response->successful() ? null : $response->body(),
            ]);

            if ($response->failed()) {
                Log::error("Airalo transaction failed: {$item->id}", ['body' => $responseBody]);
                return [
                    'status' => FulfillmentStatus::Failed,
                    'reference' => null,
                    'payload' => $responseBody,
                ];
            }

            // Airalo v2 usually returns success and a generated order ID in 'data'
            $orderData = $responseBody['data'] ?? [];
            return [
                'status' => FulfillmentStatus::Fulfilled, // Assuming synchronous response for eSIM payload
                'reference' => $orderData['id'] ?? null,
                'payload' => $responseBody,
            ];

        } catch (\Exception $e) {
            Log::error('Airalo fulfillment exception: ' . $e->getMessage());

            FulfillmentLog::create([
                'order_item_id' => $item->id,
                'provider_name' => 'airalo',
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
        try {
            if (!$item->fulfillment_reference) {
                return ['status' => FulfillmentStatus::Failed, 'payload' => []];
            }

            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withToken($this->getAccessToken())
                ->acceptJson()
                ->get("{$this->baseUrl}/orders/{$item->fulfillment_reference}");

            $responseBody = $response->json() ?? [];

            if ($response->failed()) {
                return ['status' => FulfillmentStatus::Processing, 'payload' => $responseBody];
            }

            return ['status' => FulfillmentStatus::Fulfilled, 'payload' => $responseBody];
        } catch (\Exception $e) {
            return ['status' => FulfillmentStatus::Processing, 'payload' => []];
        }
    }

    public function refund(OrderItem $item): bool
    {
        return false; // Not supported by Airalo generically
    }

    public function normalizeResponse(array $rawPayload): array
    {
        // Extract QR, ICCID, LPA, and Phone Number from Airalo payload
        $data = $rawPayload['data'] ?? [];
        
        // Airalo returns a list of sims generated
        $sims = $data['sims'] ?? [];
        $firstSim = $sims[0] ?? [];

        $lpa = $firstSim['lpa'] ?? null;
        $qrCodeUrl = $firstSim['qr_code_url'] ?? null;
        $manualActivationCode = $firstSim['matching_id'] ?? null; // For Airalo, matching_id is often the manual code
        
        // Compile manual text if applicable
        $manualText = null;
        if ($lpa && $manualActivationCode) {
            $manualText = "SM-DP+ Address: {$lpa} | Activation Code: {$manualActivationCode}";
        }

        // Specifically extract the phone number (Airalo often provides this inside 'sims' -> 'iccid' or metadata depending on the plan)
        // If the plan has voice, 'phone_number' or 'msisdn' might be present
        $phoneNumber = $firstSim['phone_number'] ?? $firstSim['msisdn'] ?? null;

        return [
            'qrcode_url' => $qrCodeUrl,
            'qr_manual_code' => $manualText,
            'iccid' => $firstSim['iccid'] ?? null,
            'lpa' => $lpa,
            'phone_number' => $phoneNumber,
            'provider_reference' => $data['id'] ?? null,
            'network' => $data['operator'] ?? null,
            'raw_response' => $rawPayload,
            
            // Format to generic pins/esim array for UI backwards compatibility
            'pins' => [],
            'esim' => [
                'iccid' => $firstSim['iccid'] ?? null,
                'lpaUrl' => $lpa,
                'manualActivationCode' => $manualActivationCode,
            ]
        ];
    }
}
