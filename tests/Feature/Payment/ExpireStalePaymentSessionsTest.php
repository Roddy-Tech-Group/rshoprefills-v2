<?php

namespace Tests\Feature\Payment;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Services\OrderService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Interfaces\PaymentProviderInterface;
use App\Domain\Payment\Jobs\ExpireStalePaymentSessionsJob;
use App\Domain\Payment\Services\PaymentGatewayFactory;
use App\Domain\Payment\Services\PaymentSessionService;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The stale-session sweeper must drive EVERY record an abandoned checkout
 * created to a terminal state: session -> expired, order -> cancelled, and
 * the PaymentAttempt -> expired. The attempt is what the admin Transactions
 * page lists - leaving it on `pending` showed abandoned checkouts as Pending
 * in admin forever while the customer's own dashboard said Cancelled.
 */
class ExpireStalePaymentSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function staleCheckout(): array
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'payment_method' => 'flutterwave',
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'flutterwave',
            'idempotency_key' => (string) Str::uuid(),
            'currency' => 'USD',
            'amount' => 25,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $session = PaymentSession::create([
            'payment_attempt_id' => $attempt->id,
            'provider' => 'flutterwave',
            'session_type' => 'card',
            'status' => 'awaiting_confirmation',
            'client_reference' => 'SESS_'.Str::random(20),
            'amount' => 25.00,
            'currency' => 'USD',
            'display_currency' => 'USD',
            'customer_email' => $user->email,
            'expires_at' => now()->subMinutes(20),
        ]);

        return [$order, $attempt, $session];
    }

    /** Force the gateway to report "unpaid" so the checkout is genuinely abandoned. */
    private function fakeGatewayUnpaid(): void
    {
        $provider = $this->createMock(PaymentProviderInterface::class);
        $provider->method('verifyPayment')->willReturn(false);
        $factory = $this->createMock(PaymentGatewayFactory::class);
        $factory->method('getProvider')->willReturn($provider);
        $this->app->instance(PaymentGatewayFactory::class, $factory);
    }

    public function test_abandoned_checkout_reaches_terminal_state_everywhere(): void
    {
        $this->fakeGatewayUnpaid();
        [$order, $attempt, $session] = $this->staleCheckout();

        (new ExpireStalePaymentSessionsJob)->handle(
            app(PaymentSessionService::class),
            app(OrderService::class),
        );

        $this->assertSame('expired', $session->fresh()->status);
        $this->assertSame(PaymentStatus::Expired, $attempt->fresh()->payment_status);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->order_status);
        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);
    }

    public function test_paid_attempt_is_never_touched_by_the_sweeper(): void
    {
        [$order, $attempt, $session] = $this->staleCheckout();

        // A late webhook confirmed the payment between TTL and sweep.
        $attempt->update(['payment_status' => PaymentStatus::Paid]);
        $order->update(['payment_status' => PaymentStatus::Paid]);

        (new ExpireStalePaymentSessionsJob)->handle(
            app(PaymentSessionService::class),
            app(OrderService::class),
        );

        $this->assertSame(PaymentStatus::Paid, $attempt->fresh()->payment_status);
        $this->assertNotSame(OrderStatus::Cancelled, $order->fresh()->order_status);
    }
}
