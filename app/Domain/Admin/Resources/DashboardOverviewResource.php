<?php

namespace App\Domain\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_users' => $this['total_users'] ?? 0,
            'total_orders' => $this['total_orders'] ?? 0,
            'total_revenue' => $this['total_revenue'] ?? 0.0,
            'transactions_count' => $this['transactions_count'] ?? 0,
            'success_rate' => $this['success_rate'] ?? 0.0,
            'wallet_balance_total' => $this['wallet_balance_total'] ?? 0.0,
        ];
    }
}
