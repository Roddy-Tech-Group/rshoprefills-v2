<?php

namespace App\Domain\Notification\Services;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceService
{
    /**
     * Get preferences for a user, creating default ones if not present.
     */
    public function getPreferences(User $user): NotificationPreference
    {
        return NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_enabled' => true,
                'marketing_enabled' => true,
                'order_notifications' => true,
                'wallet_notifications' => true,
                'security_notifications' => true,
            ]
        );
    }

    /**
     * Update notification preferences for a user.
     */
    public function updatePreferences(User $user, array $settings): NotificationPreference
    {
        $prefs = $this->getPreferences($user);
        $prefs->update($settings);

        return $prefs;
    }

    /**
     * Check if a specific notification category is allowed for a user.
     */
    public function isAllowed(User $user, string $category, string $channel = 'email'): bool
    {
        $prefs = $this->getPreferences($user);

        // Global check
        if ($channel === 'email' && ! $prefs->email_enabled) {
            return false;
        }

        if ($channel === 'push' && ! $prefs->push_enabled) {
            return false;
        }

        return match ($category) {
            'marketing' => (bool) $prefs->marketing_enabled,
            'order' => (bool) $prefs->order_notifications,
            'wallet' => (bool) $prefs->wallet_notifications,
            'security' => (bool) $prefs->security_notifications,
            default => true,
        };
    }
}
