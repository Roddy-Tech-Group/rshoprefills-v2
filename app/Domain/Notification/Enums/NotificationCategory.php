<?php

namespace App\Domain\Notification\Enums;

enum NotificationCategory: string
{
    case Order = 'order';
    case Wallet = 'wallet';
    case Security = 'security';
    case Marketing = 'marketing';
    case Engagement = 'engagement';
    case Travel = 'travel';
    case System = 'system';
}
