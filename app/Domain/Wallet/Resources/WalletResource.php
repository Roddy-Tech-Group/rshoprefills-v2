<?php

namespace App\Domain\Wallet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency->value,
            'symbol' => $this->currency->symbol(),
            'balance' => (float) $this->balance,
            'locked_balance' => (float) $this->locked_balance,
            'available_balance' => $this->availableBalance(),
            'is_active' => $this->is_active,
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
        ];
    }
}
