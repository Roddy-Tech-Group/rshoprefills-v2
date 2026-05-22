<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\DTOs\NotificationPayload;
use App\Domain\Notification\Enums\DeliveryStatus;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Providers\MailProviderInterface;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly MailProviderInterface $mailProvider
    ) {}

    public function send(NotificationPayload $payload, ?Notification $dbNotification = null): void
    {
        if (! $payload->mailable) {
            Log::warning('EmailChannel called without a Mailable object.');

            return;
        }

        try {
            // Render the mailable to HTML
            $htmlBody = $payload->mailable->render();

            // Deliver email via provider
            $response = $this->mailProvider->send(
                to: $payload->user->email,
                subject: $payload->title,
                htmlBody: $htmlBody
            );

            // Update database notification if provided
            if ($dbNotification) {
                $dbNotification->update([
                    'status' => DeliveryStatus::Sent,
                    'sent_at' => now(),
                ]);
            }

            // Record successful audit trail
            NotificationDelivery::create([
                'notification_id' => $dbNotification?->id,
                'provider' => 'resend',
                'channel' => NotificationChannel::Email,
                'recipient' => $payload->user->email,
                'status' => DeliveryStatus::Sent,
                'response_payload' => $response,
                'attempted_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('EmailChannel delivery failed', [
                'recipient' => $payload->user->email,
                'error' => $e->getMessage(),
            ]);

            // Update database notification to failed if provided
            if ($dbNotification) {
                $dbNotification->update([
                    'status' => DeliveryStatus::Failed,
                    'failed_at' => now(),
                ]);
            }

            // Record failed audit trail
            NotificationDelivery::create([
                'notification_id' => $dbNotification?->id,
                'provider' => 'resend',
                'channel' => NotificationChannel::Email,
                'recipient' => $payload->user->email,
                'status' => DeliveryStatus::Failed,
                'error_message' => $e->getMessage(),
                'attempted_at' => now(),
            ]);

            // Do NOT re-throw. A failed notification email must never break the
            // action that triggered it (checkout, wallet funding, etc.). The
            // failure is logged + recorded above for retry/audit.
        }
    }
}
