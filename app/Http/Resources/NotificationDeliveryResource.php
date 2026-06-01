<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notification_id' => $this->notification_id,
            'provider' => $this->provider,
            'channel' => $this->channel->value,
            'recipient' => $this->recipient,
            'status' => $this->status->value,
            'error_message' => $this->error_message,
            'attempted_at' => $this->attempted_at->toIso8601String(),
            'user' => $this->relationLoaded('notification') && $this->notification->relationLoaded('user')
                ? [
                    'id' => $this->notification->user->id,
                    'name' => $this->notification->user->name,
                    'email' => $this->notification->user->email,
                ]
                : null,
        ];
    }
}
