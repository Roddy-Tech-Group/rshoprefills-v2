<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Careers has been removed from the storefront (footer link + contact page
 * tile), so its contact-group setting is now orphaned. Drop it so the admin
 * System Settings contact group no longer renders an unused "Careers / HR
 * inbox" field.
 */
return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::query()->where('key', 'contact.email_careers')->delete();
        Cache::forget('site_setting:contact.email_careers');
    }

    public function down(): void
    {
        SiteSetting::put(
            'contact.email_careers',
            '',
            'contact',
            'Careers / HR inbox. Surfaces a "Careers" tile when set.',
        );
    }
};
