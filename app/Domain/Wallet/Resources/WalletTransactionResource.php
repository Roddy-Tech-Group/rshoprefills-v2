<?php

namespace App\Domain\Wallet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'category' => $this->transaction_category->value,
            'currency' => $this->currency->value,
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'reference' => $this->reference,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
