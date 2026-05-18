<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'session_type' => $this->session_type,
            'status' => $this->status,
            'client_reference' => $this->client_reference,
            'amount' => (float)$this->amount,
            'currency' => $this->currency,
            'display_currency' => $this->display_currency,
            'payment_payload' => $this->payment_payload,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
        ];
    }
}
