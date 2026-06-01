<?php

namespace App\Jobs;

use App\Mail\NewsletterBroadcastMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Broadcasts a single newsletter campaign to every active subscriber.
 *
 * Runs on the queue so the admin's "Send" click returns immediately instead
 * of waiting for hundreds of mail provider round-trips. Iterates active
 * subscribers in chunks of 50 and queues a dedicated send per recipient —
 * that way one bounced address doesn't poison the rest of the batch and
 * Resend / SMTP rate limits are easy to reason about.
 */
class SendNewsletterBroadcastJob implements ShouldQueue
{
    use Queueable;

    /**
     * Cap how long a single broadcast job can run. 30 minutes is enough for
     * ~10k subscribers at modest rates while still timing out gracefully if
     * the provider is unreachable.
     */
    public int $timeout = 1800;

    public function __construct(
        public string $subjectLine,
        public string $bodyContent,
        public bool $isHtml = false,
    ) {}

    public function handle(): void
    {
        $sent = 0;
        $failed = 0;

        NewsletterSubscriber::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->chunkById(50, function ($chunk) use (&$sent, &$failed) {
                foreach ($chunk as $subscriber) {
                    try {
                        Mail::to($subscriber->email)->send(new NewsletterBroadcastMail(
                            subjectLine: $this->subjectLine,
                            bodyContent: $this->bodyContent,
                            isHtml: $this->isHtml,
                        ));
                        $sent++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('Newsletter send failed', [
                            'subscriber_id' => $subscriber->id,
                            'email' => $subscriber->email,
                            'reason' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Newsletter broadcast complete', [
            'subject' => $this->subjectLine,
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }
}
