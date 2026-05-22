<?php

namespace App\Domain\Notification\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Log;

/**
 * Central entry point for admin-dashboard notifications. Resilient by design —
 * a notification failure must never break the action that triggered it.
 */
class AdminNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function push(string $type, string $title, ?string $message = null, ?string $url = null, array $data = []): void
    {
        try {
            AdminNotification::create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'url' => $url,
                'data' => $data ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Admin notification failed: '.$e->getMessage());
        }
    }
}
