<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\DTOs\NotificationPayload;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\DeliveryStatus;
use App\Models\Notification;
use App\Models\NotificationDelivery;

class DatabaseChannel implements NotificationChannelInterface
{
    public function send(NotificationPayload $payload, ?Notification $dbNotification = null): void
    {
        $notification = $dbNotification ?? Notification::create([
            'user_id' => $payload->user->id,
            'type' => $payload->mailable ? get_class($payload->mailable) : 'App\Domain\Notification\General',
            'title' => $payload->title,
            'message' => $payload->message,
            'channel' => NotificationChannel::Database,
            'status' => DeliveryStatus::Sent,
            'priority' => $payload->priority,
            'metadata' => $payload->metadata,
            'sent_at' => now(),
        ]);

        if ($dbNotification && $notification->status !== DeliveryStatus::Sent) {
            $notification->update([
                'status' => DeliveryStatus::Sent,
                'sent_at' => now(),
            ]);
        }

        // Audit log
        NotificationDelivery::create([
            'notification_id' => $notification->id,
            'provider' => 'database',
            'channel' => NotificationChannel::Database,
            'recipient' => (string) $payload->user->id,
            'status' => DeliveryStatus::Sent,
            'response_payload' => ['notification_id' => $notification->id],
            'attempted_at' => now(),
        ]);
    }
}
