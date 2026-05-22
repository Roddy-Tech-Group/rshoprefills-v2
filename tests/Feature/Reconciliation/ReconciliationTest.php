<?php

namespace Tests\Feature\Reconciliation;

use App\Domain\Reconciliation\Models\ReconciliationReport;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Wallet\Services\WalletService;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentSession;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\CriticalSystemAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function walletFor(User $user): Wallet
    {
        return Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 0,
            'locked_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function orphanCandidate(string $email): PaymentSession
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'payment_method' => 'wallet',
        ]);
        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'wallet',
            'idempotency_key' => (string) Str::uuid(),
            'currency' => 'USD',
            'amount' => 25,
        ]);

        return PaymentSession::create([
            'payment_attempt_id' => $attempt->id,
            'provider' => 'wallet',
            'session_type' => 'wallet',
            'status' => 'pending',
            'client_reference' => 'SESS_'.Str::random(20),
            'amount' => 25.00,
            'currency' => 'USD',
            'display_currency' => 'USD',
            'customer_email' => $email,
        ]);
    }

    public function test_wallet_reconciliation_is_clean_when_balances_match(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        app(WalletService::class)->credit($wallet, 100, TransactionCategory::Funding, 'topup', 'ref-1', 'idem-1');

        $this->artisan('reconcile:wallet-balances');

        $report = ReconciliationReport::where('type', 'wallet_balance')->latest('id')->first();
        $this->assertSame('clean', $report->status);
        Notification::assertNothingSent();
    }

    public function test_wallet_reconciliation_flags_a_tampered_balance_and_alerts_admin(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        app(WalletService::class)->credit($wallet, 100, TransactionCategory::Funding, 'topup', 'ref-2', 'idem-2');

        // Tamper: the stored balance no longer matches the transaction history.
        $wallet->update(['balance' => 40]);

        $this->artisan('reconcile:wallet-balances');

        $report = ReconciliationReport::where('type', 'wallet_balance')->latest('id')->first();
        $this->assertSame('anomalies_found', $report->status);
        $this->assertCount(1, $report->anomalies_found);
        Notification::assertSentOnDemand(CriticalSystemAlert::class);
    }

    public function test_orphaned_sessions_older_than_the_threshold_are_auto_failed(): void
    {
        $session = $this->orphanCandidate('old@example.test');
        $session->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->artisan('reconcile:orphaned-sessions');

        $this->assertSame('failed', $session->fresh()->status);
        $report = ReconciliationReport::where('type', 'orphaned_sessions')->latest('id')->first();
        $this->assertSame('anomalies_found', $report->status);
    }

    public function test_recent_sessions_are_left_untouched(): void
    {
        $session = $this->orphanCandidate('new@example.test');

        $this->artisan('reconcile:orphaned-sessions');

        $this->assertSame('pending', $session->fresh()->status);
    }
}
