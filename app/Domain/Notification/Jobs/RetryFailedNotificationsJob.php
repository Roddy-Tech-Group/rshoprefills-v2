<?php

namespace App\Domain\Notification\Jobs;

use App\Domain\Notification\Enums\DeliveryStatus;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sweep failed notifications and re-dispatch them for delivery. Runs on a
 * schedule (every 15 minutes) AND on-demand from the admin notifications page
 * via the "Retry all failed" button.
 *
 * The retry counter lives in `notifications.metadata.retry_count` so we don't
 * need a schema migration. Each notification is capped at MAX_AUTO_RETRIES so
 * a permanently broken recipient (suspended domain, bounced address) doesn't
 * churn forever in the queue.
 */
class RetryFailedNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Auto-retry hard ceiling per notification. The admin can still hit the
     * per-row Retry button manually after this is exhausted - that path
     * intentionally bypasses the counter.
     */
    public const MAX_AUTO_RETRIES = 3;

    /**
     * Skip notifications that failed less than this many minutes ago. Gives
     * transient outages (DNS blip, Resend 5xx wave) a chance to recover
     * before we hammer the provider again.
     */
    public const MIN_BACKOFF_MINUTES = 5;

    public function handle(): void
    {
        $cutoff = now()->subMinutes(self::MIN_BACKOFF_MINUTES);

        $failed = Notification::query()
            ->with('user')
            ->where('status', DeliveryStatus::Failed)
            ->where('failed_at', '<=', $cutoff)
            ->get()
            ->filter(fn (Notification $n) => (int) ($n->metadata['retry_count'] ?? 0) < self::MAX_AUTO_RETRIES);

        if ($failed->isEmpty()) {
            return;
        }

        Log::info('Auto-retry sweep starting for failed notifications.', [
            'eligible_count' => $failed->count(),
        ]);

        $retried = 0;
        foreach ($failed as $notification) {
            $metadata = (array) ($notification->metadata ?? []);
            $metadata['retry_count'] = (int) ($metadata['retry_count'] ?? 0) + 1;
            $metadata['last_auto_retry_at'] = now()->toIso8601String();

            $notification->update([
                'status' => DeliveryStatus::Pending,
                'failed_at' => null,
                'metadata' => $metadata,
            ]);

            // Re-build the original mailable when `type` holds a class name.
            $mailable = null;
            if ($notification->type && class_exists($notification->type)) {
                try {
                    $mailable = new $notification->type($notification->user);
                } catch (\Throwable $e) {
                    Log::warning('Auto-retry could not reconstruct mailable.', [
                        'notification_id' => $notification->id,
                        'type' => $notification->type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            SendAsynchronousNotificationJob::dispatch(
                user: $notification->user,
                title: $notification->title,
                message: $notification->message,
                mailable: $mailable,
                priority: $notification->priority,
                metadata: $metadata,
                channels: [$notification->channel],
            );

            $retried++;
        }

        Log::info('Auto-retry sweep dispatched re-attempts.', [
            'retried_count' => $retried,
        ]);
    }
}
