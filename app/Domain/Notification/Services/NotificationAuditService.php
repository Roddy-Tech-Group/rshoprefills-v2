<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Enums\DeliveryStatus;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationAuditService
{
    /**
     * Get paginated delivery logs for admin review.
     */
    public function getDeliveryLogs(int $perPage = 15): LengthAwarePaginator
    {
        return NotificationDelivery::with('notification.user')
            ->latest('attempted_at')
            ->paginate($perPage);
    }

    /**
     * Get system-wide notification delivery metrics.
     */
    public function getSystemMetrics(): array
    {
        $totalAttempts = NotificationDelivery::count();
        $successful = NotificationDelivery::where('status', DeliveryStatus::Sent)->count();
        $failed = NotificationDelivery::where('status', DeliveryStatus::Failed)->count();

        $successRate = $totalAttempts > 0 ? round(($successful / $totalAttempts) * 100, 2) : 100.0;

        return [
            'total_delivery_attempts' => $totalAttempts,
            'successful_deliveries' => $successful,
            'failed_deliveries' => $failed,
            'success_rate' => $successRate,
        ];
    }
}
