<?php

namespace App\Domain\Notification\Enums;

enum InteractionType: string
{
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Dismissed = 'dismissed';
}
