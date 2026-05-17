<?php

namespace App\Domain\Notification\Enums;

enum NotificationPriority: string
{
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';
}
