<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'quantity' => $this->quantity,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant_id' => $this->product_variant_id,
            'pricing' => [
                'display_currency' => $this->display_currency,
                'display_amount' => $this->display_amount,
                'subtotal_usd' => $this->subtotal_snapshot,
            ],
            'metadata' => $this->metadata_snapshot,
        ];
    }
}
