<?php

namespace App\Domain\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LatestUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'wallet_balance' => (float) optional($this->wallet)->balance ?? 0.0,
            'status' => $this->email_verified_at ? 'verified' : 'unverified',
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
