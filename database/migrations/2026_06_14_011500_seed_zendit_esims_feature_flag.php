<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Feature flag to switch Zendit eSIMs on/off storefront-wide without a deploy.
 * Off hides every Zendit-supplied eSIM from the eSIM landing, country pages and
 * search; Airalo eSIMs stay visible. Lives in the `features` group so it shows
 * up beside the other toggles on the admin System Settings page.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! SiteSetting::query()->where('key', 'features.zendit_esims_enabled')->exists()) {
            SiteSetting::put(
                'features.zendit_esims_enabled',
                'on',
                'features',
                'Off hides all Zendit-supplied eSIMs from the storefront (Airalo eSIMs stay visible).',
            );
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('key', 'features.zendit_esims_enabled')->delete();
    }
};
