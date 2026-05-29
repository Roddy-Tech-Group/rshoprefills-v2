<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'email_enabled' => (bool) $this->email_enabled,
            'marketing_enabled' => (bool) $this->marketing_enabled,
            'order_notifications' => (bool) $this->order_notifications,
            'wallet_notifications' => (bool) $this->wallet_notifications,
            'security_notifications' => (bool) $this->security_notifications,
        ];
    }
}
