<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
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
            'sku' => $this->sku,
            'currency' => $this->currency,
            'face_value' => (float) $this->face_value,
            'retail_price' => (float) $this->retail_price,
            'is_variable' => $this->is_variable,
            'min_amount' => $this->min_amount ? (float) $this->min_amount : null,
            'max_amount' => $this->max_amount ? (float) $this->max_amount : null,
            'is_available' => $this->is_available,
        ];
    }
}
