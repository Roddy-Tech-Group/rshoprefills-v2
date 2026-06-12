<?php

namespace Tests\Feature\Notification;

use App\Domain\Notification\Channels\EmailChannel;
use App\Domain\Notification\DTOs\NotificationPayload;
use App\Domain\Notification\Mail\OrderPlacedMail;
use App\Domain\Notification\Mail\WelcomeMail;
use App\Domain\Notification\Providers\MailProviderInterface;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dispatcher-driven emails (welcome, order placed, refunds, wallet...) go out
 * through EmailChannel -> the HTTP mail provider. The channel must render the
 * mailable through the real Mailer pipeline so inline CID images (the brand
 * logo) exist, and forward them to the provider - a plain render() has no
 * $message and ships emails with broken logos.
 */
class EmailChannelInlineImagesTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $captured = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->captured = [];
        $test = $this;

        $this->app->instance(MailProviderInterface::class, new class($test) implements MailProviderInterface
        {
            public function __construct(private EmailChannelInlineImagesTest $test) {}

            public function send(string $to, string $subject, string $htmlBody, array $headers = [], array $attachments = []): array
            {
                $this->test->capture(compact('to', 'subject', 'htmlBody', 'attachments'));

                return ['id' => 'spy', 'status' => 'success'];
            }
        });
    }

    /** @param array<string, mixed> $args */
    public function capture(array $args): void
    {
        $this->captured = $args;
    }

    public function test_welcome_email_carries_the_logo_as_an_inline_cid_attachment(): void
    {
        $user = User::factory()->create();

        app(EmailChannel::class)->send(new NotificationPayload(
            user: $user,
            title: 'Welcome to RshopRefills!',
            message: 'Welcome aboard.',
            mailable: new WelcomeMail($user),
        ));

        $this->assertNotEmpty($this->captured, 'Provider was never called');
        $this->assertStringContainsString('cid:', $this->captured['htmlBody']);
        $this->assertStringNotContainsString('assets/email-logo.png', $this->captured['htmlBody']);

        $logo = collect($this->captured['attachments'])->firstWhere('filename', 'email-logo.png');
        $this->assertNotNull($logo, 'Expected the logo as an attachment');
        $this->assertNotEmpty($logo['content_id'] ?? null, 'Logo attachment must carry a content_id for CID rendering');
        $this->assertStringContainsString('cid:'.$logo['content_id'], $this->captured['htmlBody']);
    }

    public function test_order_placed_email_carries_the_logo_too(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'RSR-TEST-MAIL01',
            'cart_id' => null,
            'settlement_currency' => 'USD',
            'display_currency' => 'USD',
            'subtotal_amount' => 7,
            'markup_amount' => 0,
            'total_amount' => 7,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'fulfillment_status' => 'processing',
            'order_status' => 'processing',
            'placed_at' => now(),
            'metadata' => ['exchange_rate' => 1.0, 'settlement_total_usd' => 7, 'settlement_subtotal_usd' => 7],
        ]);

        app(EmailChannel::class)->send(new NotificationPayload(
            user: $user,
            title: 'Order confirmed',
            message: 'We received your order.',
            mailable: new OrderPlacedMail($user, $order),
        ));

        $logo = collect($this->captured['attachments'] ?? [])->firstWhere('filename', 'email-logo.png');
        $this->assertNotNull($logo, 'Expected the logo as an attachment');
        $this->assertNotEmpty($logo['content_id'] ?? null);
    }
}
