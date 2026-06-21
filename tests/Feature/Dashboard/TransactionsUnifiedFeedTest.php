<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Wallet\Services\WalletService;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * The Transactions page is a unified money feed: wallet ledger movements PLUS
 * paid gateway order payments (card / mobile money / crypto). Card-paid orders
 * never touch a wallet, so before the merge they were invisible to customers -
 * exactly the launch-day confusion ("where did my payment go?").
 */
class TransactionsUnifiedFeedTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrder(User $user, string $gateway, array $verification = []): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'RSR-'.Str::upper(Str::random(8)),
            'payment_method' => $gateway,
            'payment_status' => PaymentStatus::Paid,
            'display_currency' => 'XAF',
            'total_amount' => 1561.68,
        ]);

        PaymentAttempt::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => $gateway,
            'idempotency_key' => (string) Str::uuid(),
            'currency' => 'XAF',
            'amount' => 1561.68,
            'payment_status' => PaymentStatus::Paid,
            'gateway_reference' => 'TX-'.Str::random(6),
            'verification_payload' => $verification,
            'confirmed_at' => now(),
        ]);

        return $order;
    }

    public function test_card_and_momo_payments_appear_in_the_feed(): void
    {
        $user = User::factory()->create();
        $momoOrder = $this->paidOrder($user, 'flutterwave', ['data' => ['payment_type' => 'mobilemoneygh']]);
        $cardOrder = $this->paidOrder($user, 'flutterwave', ['data' => ['payment_type' => 'card']]);

        Volt::actingAs($user)->test('dashboard.transactions')
            ->assertSee('Mobile Money payment')
            ->assertSee('Card payment')
            ->assertSee($momoOrder->order_number)
            ->assertSee($cardOrder->order_number);
    }

    public function test_crypto_payment_is_labelled_generically(): void
    {
        $user = User::factory()->create();
        $this->paidOrder($user, 'nowpayments');

        Volt::actingAs($user)->test('dashboard.transactions')
            ->assertSee('Crypto payment');
    }

    public function test_wallet_gateway_payment_is_not_duplicated_in_the_feed(): void
    {
        $user = User::factory()->create();
        // Wallet-paid orders already show via their ledger debit; the gateway
        // attempt must NOT also appear, or the spend would be double-counted.
        $this->paidOrder($user, 'wallet');

        Volt::actingAs($user)->test('dashboard.transactions')
            ->assertDontSee('Wallet payment');
    }

    public function test_wallet_ledger_credit_still_shows_alongside_payments(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->getOrCreateWallet($user, Currency::USD);
        app(WalletService::class)->credit($wallet, 50, TransactionCategory::Funding, 'Wallet top-up', 'ref-1', 'idem-1');
        $this->paidOrder($user, 'flutterwave', ['data' => ['payment_type' => 'card']]);

        Volt::actingAs($user)->test('dashboard.transactions')
            ->assertSee('Wallet top-up')
            ->assertSee('Card payment');
    }
}
