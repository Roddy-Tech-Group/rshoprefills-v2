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

    private function orderFor(User $user, float $total, string $currency = 'NGN'): Order
    {
        $order = new Order;
        $order->forceFill([
            'order_number' => 'ORD-TEST-1',
            'display_currency' => $currency,
            'total_amount' => $total,
        ]);
        $order->setRelation('user', $user);

        return $order;
    }

    public function test_large_transaction_admin_alert_builds_without_null_currency_crash(): void
    {
        $user = User::factory()->create();
        // total far above the default 5000 suspicious threshold -> large branch.
        $order = $this->orderFor($user, 999999.00, 'NGN');

        $messages = [];
        $dispatcher = $this->mock(NotificationDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->twice() // customer confirmation + admin alert
            ->andReturnUsing(function (...$args) use (&$messages) {
                $messages[] = $args[2]; // message is the 3rd positional arg
            });

        app(SendOrderConfirmationListener::class)
            ->onPaymentConfirmed(new PaymentConfirmed($order, new PaymentAttempt));

        $adminMessage = collect($messages)->first(fn ($m) => str_contains($m, 'exceptionally large'));
        $this->assertNotNull($adminMessage);
        $this->assertStringContainsString('NGN', $adminMessage);
    }

    public function test_refund_message_builds_without_null_currency_crash(): void
    {
        $user = User::factory()->create();
        $order = $this->orderFor($user, 2800.00, 'GHS');

        $messages = [];
        $dispatcher = $this->mock(NotificationDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andReturnUsing(function (...$args) use (&$messages) {
                $messages[] = $args[2];
            });

        app(SendOrderConfirmationListener::class)
            ->onRefundIssued(new RefundIssued($order, 100.0, 'duplicate charge'));

        $this->assertStringContainsString('GHS', $messages[0]);
    }
}
