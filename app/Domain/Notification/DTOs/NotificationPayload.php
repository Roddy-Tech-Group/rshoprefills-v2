<?php

namespace App\Domain\Notification\DTOs;

use App\Domain\Notification\Enums\NotificationPriority;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;

class NotificationPayload
{
    public function __construct(
        public readonly User $user,
        public readonly string $title,
        public readonly string $message,
        public readonly ?Mailable $mailable = null,
        public readonly NotificationPriority $priority = NotificationPriority::Normal,
        public readonly array $metadata = []
    ) {}
}
