<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Notification\Enums\DeliveryStatus;
use App\Domain\Notification\Jobs\SendAsynchronousNotificationJob;
use App\Domain\Notification\Services\NotificationAuditService;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationDeliveryResource;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationAdminApiController extends Controller
{
    public function __construct(
        private readonly NotificationAuditService $auditService
    ) {}

    /**
     * Audit list of all notifications in the system.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::with('user')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => NotificationResource::collection($notifications),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Audit list of delivery attempts and error messages.
     */
    public function deliveries(Request $request): JsonResponse
    {
        $deliveries = $this->auditService->getDeliveryLogs($request->integer('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => NotificationDeliveryResource::collection($deliveries),
            'pagination' => [
                'total' => $deliveries->total(),
                'per_page' => $deliveries->perPage(),
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
            ],
        ]);
    }

    /**
     * Trigger manual queue retry for a failed notification attempt.
     */
    public function retry(Request $request, string $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);

        if ($notification->status === DeliveryStatus::Sent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification has already been successfully sent.',
            ], 400);
        }

        // Reset status to pending
        $notification->update([
            'status' => DeliveryStatus::Pending,
            'failed_at' => null,
        ]);

        // Resolve original mailable name if present
        $mailable = null;
        if (class_exists($notification->type)) {
            $mailable = new $notification->type($notification->user);
        }

        // Re-dispatch original notification
        SendAsynchronousNotificationJob::dispatch(
            user: $notification->user,
            title: $notification->title,
            message: $notification->message,
            mailable: $mailable,
            priority: $notification->priority,
            metadata: $notification->metadata ?? [],
            channels: [$notification->channel]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Re-queued failed notification for immediate delivery retry.',
        ]);
    }

    /**
     * Expose delivery performance metrics.
     */
    public function metrics(Request $request): JsonResponse
    {
        $metrics = $this->auditService->getSystemMetrics();

        return response()->json([
            'status' => 'success',
            'data' => $metrics,
        ]);
    }
}
