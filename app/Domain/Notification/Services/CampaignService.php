<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Jobs\SendAsynchronousNotificationJob;
use App\Models\NotificationCampaign;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * Dispatch a campaign to its intended audience.
     */
    public function dispatchCampaign(NotificationCampaign $campaign): void
    {
        if ($campaign->status !== 'active') {
            return;
        }

        $template = $campaign->template;
        if (!$template) {
            Log::error("Campaign {$campaign->id} has no template.");
            return;
        }

        // Simplistic audience evaluation (extend as needed)
        $query = User::query();
        
        if (!empty($campaign->audience_filters['country'])) {
            $query->where('country', $campaign->audience_filters['country']);
        }

        $query->chunk(500, function ($users) use ($campaign, $template) {
            foreach ($users as $user) {
                // In a real scenario, evaluate template variables here
                $title = str_replace('{{name}}', $user->name, $template->title_template);
                $body = str_replace('{{name}}', $user->name, $template->body_template);
                $url = $template->action_url;

                $this->dispatcher->dispatch(
                    user: $user,
                    title: $title,
                    message: $body,
                    category: $campaign->category,
                    mailable: null, // Depending on if channel includes email, we'd build a generic mailable
                    metadata: array_merge($template->metadata ?? [], [
                        'campaign_id' => $campaign->id,
                        'url' => $url,
                    ]),
                    idempotencyKey: 'campaign_'.$campaign->id.'_user_'.$user->id
                );
            }
        });

        $campaign->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
