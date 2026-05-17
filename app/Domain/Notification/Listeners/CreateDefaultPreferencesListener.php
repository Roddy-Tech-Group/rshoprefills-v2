<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Services\NotificationPreferenceService;
use Illuminate\Auth\Events\Registered;

class CreateDefaultPreferencesListener
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService
    ) {}

    public function handle(Registered $event): void
    {
        $this->preferenceService->getPreferences($event->user);
    }
}
