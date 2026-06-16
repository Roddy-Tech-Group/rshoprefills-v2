<?php

namespace Tests\Feature\Fulfillment;

use App\Domain\Fulfillment\Providers\AiraloFulfillmentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Airalo's submit-order response carries no eSIMs Cloud sharing block - it is
 * only returned by the Get eSIM endpoint (GET /sims/{iccid}). The normalizer
 * must backfill it so the delivery email and dashboard always have the hosted
 * portal link + access code.
 */
class AiraloSharingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Shape captured from a real production fulfilment (order
     * RSR-20260611-IUF2UZ): sims present, no sharing block anywhere.
     *
     * @return array<string, mixed>
     */
    private function orderResponseWithoutSharing(): array
    {
        return [
            'data' => [
                'id' => 1917316,
                'code' => '20260611-1917316',
                'sims' => [
                    [
                        'id' => 2211635,
                        'lpa' => 'wbg.prod.ondemandconnectivity.com',
                        'iccid' => '89852350326101470741',
                        'qrcode' => 'LPA:1$wbg.prod.ondemandconnectivity.com$YI2SX1S9WJTXQH3S',
                        'qrcode_url' => 'https://www.airalo.com/qr?id=73646523',
                        'matching_id' => 'YI2SX1S9WJTXQH3S',
                        'direct_apple_installation_url' => 'https://esimsetup.apple.com/esim_qrcode_provisioning?carddata=LPA:1$wbg.prod.ondemandconnectivity.com$YI2SX1S9WJTXQH3S',
                    ],
                ],
            ],
            'meta' => ['message' => 'success'],
        ];
    }

    public function test_normalize_backfills_sharing_from_get_esim_endpoint(): void
    {
        Http::fake([
            '*/token' => Http::response(['data' => ['access_token' => 'test-token']]),
            '*/sims/89852350326101470741' => Http::response([
                'data' => [
                    'iccid' => '89852350326101470741',
                    'sharing' => [
                        'link' => 'https://esims.cloud/rshoprefill/xpszc-dkcgxfh5',
                        'access_code' => '4436',
                    ],
                ],
            ]),
        ]);

        $normalized = app(AiraloFulfillmentProvider::class)->normalizeResponse($this->orderResponseWithoutSharing());

        $this->assertSame('https://esims.cloud/rshoprefill/xpszc-dkcgxfh5', $normalized['sharing_link']);
        $this->assertSame('4436', $normalized['sharing_access_code']);
        $this->assertSame('https://esims.cloud/rshoprefill/xpszc-dkcgxfh5', $normalized['esim']['sharingLink']);
        $this->assertSame('4436', $normalized['esim']['sharingAccessCode']);
    }

    public function test_normalize_keeps_sharing_from_order_response_without_extra_call(): void
    {
        Http::fake();

        $payload = $this->orderResponseWithoutSharing();
        $payload['data']['sims'][0]['sharing'] = [
            'link' => 'https://esims.cloud/rshoprefill/from-order',
            'access_code' => '1111',
        ];

        $normalized = app(AiraloFulfillmentProvider::class)->normalizeResponse($payload);

        $this->assertSame('https://esims.cloud/rshoprefill/from-order', $normalized['sharing_link']);
        $this->assertSame('1111', $normalized['sharing_access_code']);
        Http::assertNothingSent();
    }

    public function test_normalize_survives_a_failing_get_esim_call(): void
    {
        Http::fake([
            '*/token' => Http::response(['data' => ['access_token' => 'test-token']]),
            '*/sims/*' => Http::response(['message' => 'not found'], 404),
        ]);

        $normalized = app(AiraloFulfillmentProvider::class)->normalizeResponse($this->orderResponseWithoutSharing());

        $this->assertNull($normalized['sharing_link']);
        $this->assertNull($normalized['sharing_access_code']);
        // Everything else still normalizes - the install path must not break
        // because the portal link was unavailable.
        $this->assertSame('89852350326101470741', $normalized['iccid']);
        $this->assertSame('YI2SX1S9WJTXQH3S', $normalized['esim']['manualActivationCode']);
    }
}
