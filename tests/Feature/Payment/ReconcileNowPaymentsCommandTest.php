<?php

namespace Tests\Feature\Payment;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Jobs\VerifyPaymentJob;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReconcileNowPaymentsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(array $overrides = []): PaymentAttempt
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'payment_method' => 'nowpayments',
        ]);

        return PaymentAttempt::create(array_merge([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'nowpayments',
            'gateway_reference' => 'NP-'.Str::random(8),
            'idempotency_key' => (string) Str::uuid(),
            'currency' => 'USD',
            'amount' => 25,
        ], $overrides));
    }

    public function test_redispatches_verification_for_pending_crypto_attempts(): void
    {
        Bus::fake();
        $this->attempt();
        $this->attempt();

        $this->artisan('reconcile:nowpayments')
            ->expectsOutputToContain('Found 2 pending NOWPayments attempt(s)')
            ->assertSuccessful();

        Bus::assertDispatchedSync(VerifyPaymentJob::class, 2);
    }

    public function test_skips_terminal_and_referenceless_attempts(): void
    {
        Bus::fake();
        $this->attempt(['payment_status' => PaymentStatus::Paid]);
        $this->attempt(['payment_status' => PaymentStatus::Failed]);
        $this->attempt(['gateway_reference' => null]);

        $this->artisan('reconcile:nowpayments')
            ->expectsOutputToContain('No pending NOWPayments attempts')
            ->assertSuccessful();

        Bus::assertNotDispatchedSync(VerifyPaymentJob::class);
    }

    public function test_dry_run_lists_candidates_without_dispatching(): void
    {
        Bus::fake();
        $this->attempt();

        $this->artisan('reconcile:nowpayments --dry-run')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        Bus::assertNotDispatchedSync(VerifyPaymentJob::class);
    }
}
