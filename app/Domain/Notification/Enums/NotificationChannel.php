<?php

namespace App\Domain\Notification\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Database = 'database'; // In-App
    case Sms = 'sms';
    case Push = 'push';
    case Whatsapp = 'whatsapp';
}
