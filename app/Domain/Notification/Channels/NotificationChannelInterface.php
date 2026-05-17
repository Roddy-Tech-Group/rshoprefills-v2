<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\DTOs\NotificationPayload;
use App\Models\Notification;

interface NotificationChannelInterface
{
    /**
     * Send a notification to the specified recipient.
     */
    public function send(NotificationPayload $payload, ?Notification $dbNotification = null): void;
}
