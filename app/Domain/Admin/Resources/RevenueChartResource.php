<?php

namespace App\Domain\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevenueChartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'gift_cards' => $this['gift_cards'],
            'esim' => $this['esim'],
            'topup' => $this['topup'],
            'other' => $this['other'],
        ];
    }
}
