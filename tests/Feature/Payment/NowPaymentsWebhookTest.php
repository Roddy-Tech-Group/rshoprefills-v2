<?php

namespace Tests\Feature\Payment;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Providers\NowPaymentsProvider;
use App\Jobs\VerifyPaymentJob;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression coverage for the crypto-confirmation chain. Each of these used to be
 * broken: the webhook signature was computed over unsorted JSON (so every real
 * NOWPayments notification was rejected), the attempt was looked up by invoice_id
 * (we store payment_id), and verifyPayment hit /invoice/{id} (status lives at
 * /payment/{id}). Any one of them stopped paid orders from auto-completing.
 */
class NowPaymentsWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $ipnSecret = 'test-ipn-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.nowpayments.ipn_secret' => $this->ipnSecret]);
    }

    private function attempt(string $paymentId): PaymentAttempt
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'payment_method' => 'nowpayments',
        ]);

        return PaymentAttempt::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'nowpayments',
            'gateway_reference' => $paymentId,
            'idempotency_key' => (string) Str::uuid(),
            'currency' => 'USD',
            'amount' => 25,
        ]);
    }

    /** Sign a payload the way NOWPayments does: key-sorted JSON, HMAC-SHA512. */
    private function sign(array $payload): string
    {
        ksort($payload);

        return hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->ipnSecret);
    }

    public function test_valid_signed_webhook_matches_attempt_by_payment_id_and_verifies(): void
    {
        Bus::fake();
        $attempt = $this->attempt('5524759814');

        $payload = ['payment_id' => 5524759814, 'payment_status' => 'finished', 'price_amount' => 25, 'price_currency' => 'usd'];

        $this->withHeaders(['x-nowpayments-sig' => $this->sign($payload)])
            ->postJson(route('api.webhooks.nowpayments'), $payload)
            ->assertOk()
            ->assertJson(['message' => 'Webhook processed']);

        Bus::assertDispatched(VerifyPaymentJob::class);
        $this->assertNotNull($attempt->fresh()->webhook_payload);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        Bus::fake();
        $this->attempt('5524759814');

        $payload = ['payment_id' => 5524759814, 'payment_status' => 'finished'];

        $this->withHeaders(['x-nowpayments-sig' => 'deadbeef'])
            ->postJson(route('api.webhooks.nowpayments'), $payload)
            ->assertStatus(401);

        Bus::assertNotDispatched(VerifyPaymentJob::class);
    }

    public function test_missing_payment_id_is_rejected(): void
    {
        $payload = ['payment_status' => 'finished', 'price_amount' => 25];

        $this->withHeaders(['x-nowpayments-sig' => $this->sign($payload)])
            ->postJson(route('api.webhooks.nowpayments'), $payload)
            ->assertStatus(400);
    }

    public function test_unknown_payment_id_returns_404(): void
    {
        $payload = ['payment_id' => 999, 'payment_status' => 'finished'];

        $this->withHeaders(['x-nowpayments-sig' => $this->sign($payload)])
            ->postJson(route('api.webhooks.nowpayments'), $payload)
            ->assertStatus(404);
    }

    public function test_pending_status_is_acknowledged_without_verifying(): void
    {
        Bus::fake();
        $this->attempt('5524759814');

        $payload = ['payment_id' => 5524759814, 'payment_status' => 'waiting'];

        $this->withHeaders(['x-nowpayments-sig' => $this->sign($payload)])
            ->postJson(route('api.webhooks.nowpayments'), $payload)
            ->assertOk();

        Bus::assertNotDispatched(VerifyPaymentJob::class);
    }

    public function test_verify_payment_reads_status_from_the_payment_endpoint(): void
    {
        config(['services.nowpayments.api_key' => 'live-key-xyz']);
        $attempt = $this->attempt('5524759814');

        Http::fake([
            '*' => Http::response(['payment_id' => 5524759814, 'payment_status' => 'finished'], 200),
        ]);

        $provider = new NowPaymentsProvider;

        $this->assertTrue($provider->verifyPayment($attempt));
        $this->assertSame(PaymentStatus::Paid, $attempt->fresh()->payment_status);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/payment/5524759814'));
    }
}
