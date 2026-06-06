<?php

namespace Tests\Feature\Notification;

use App\Domain\Notification\Listeners\SendOrderConfirmationListener;
use App\Domain\Notification\Services\NotificationDispatcher;
use App\Domain\Order\Events\PaymentConfirmed;
use App\Domain\Order\Events\RefundIssued;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the production crash where the listener read the
 * non-existent {@see Order::$currency} property (Attempt to read property
 * "value" on null), which rolled back the entire payment-confirmation
 * transaction. The order exposes string `display_currency`, not an enum.
 */
class SendOrderConfirmationListenerTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $extra */
    private function orderFor(User $user, float $total, string $currency = 'NGN', array $extra = []): Order
    {
        $order = new Order;
        $order->forceFill(array_merge([
            'order_number' => 'ORD-TEST-1',
            'display_currency' => $currency,
            'total_amount' => $total,
        ], $extra));
        $order->setRelation('user', $user);

        return $order;
    }

    /** @return list<string> */
    private function capturedMessages(callable $fire, int $times): array
    {
        $messages = [];
        $dispatcher = $this->mock(NotificationDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->times($times)
            ->andReturnUsing(function (...$args) use (&$messages) {
                $messages[] = $args[2]; // message is the 3rd positional arg
            });

        $fire(app(SendOrderConfirmationListener::class));

        return $messages;
    }

    public function test_large_usd_transaction_admin_alert_builds_without_null_currency_crash(): void
    {
        $user = User::factory()->create();
        // USD total above the default 5000 threshold -> large branch.
        $order = $this->orderFor($user, 6000.00, 'USD');

        $messages = $this->capturedMessages(
            fn (SendOrderConfirmationListener $l) => $l->onPaymentConfirmed(new PaymentConfirmed($order, new PaymentAttempt)),
            times: 2, // customer confirmation + admin alert
        );

        $adminMessage = collect($messages)->first(fn ($m) => str_contains($m, 'exceptionally large'));
        $this->assertNotNull($adminMessage);
        $this->assertStringContainsString('USD', $adminMessage);
    }

    public function test_large_display_amount_with_small_usd_is_not_flagged_large(): void
    {
        $user = User::factory()->create();
        // 999,999 NGN display, but only ~$3 USD -> must NOT trip the threshold.
        $order = $this->orderFor($user, 999999.00, 'NGN', [
            'metadata' => ['settlement_total_usd' => 3.00],
        ]);

        $messages = $this->capturedMessages(
            fn (SendOrderConfirmationListener $l) => $l->onPaymentConfirmed(new PaymentConfirmed($order, new PaymentAttempt)),
            times: 2,
        );

        $this->assertNull(collect($messages)->first(fn ($m) => str_contains($m, 'exceptionally large')));
        $this->assertNotNull(collect($messages)->first(fn ($m) => str_contains($m, 'A new order')));
    }

    public function test_refund_message_builds_without_null_currency_crash(): void
    {
        $user = User::factory()->create();
        $order = $this->orderFor($user, 2800.00, 'GHS');

        $messages = $this->capturedMessages(
            fn (SendOrderConfirmationListener $l) => $l->onRefundIssued(new RefundIssued($order, 100.0, 'duplicate charge')),
            times: 1,
        );

        $this->assertStringContainsString('GHS', $messages[0]);
    }
}
