<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Seed admin-editable social-link settings. Footer / dashboard / auth /
 * contact / help / email layout all read from these keys via
 * SiteSetting::get('social.facebook') etc. Admin edits live in the
 * "social" group on the System Settings page (same structure as the
 * existing product / catalog settings groups).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['key' => 'social.facebook',  'value' => 'https://facebook.com/rshoprefills',  'description' => 'Public Facebook page URL.'],
            ['key' => 'social.x',         'value' => 'https://x.com/rshoprefills',         'description' => 'Public X (Twitter) profile URL.'],
            ['key' => 'social.tiktok',    'value' => 'https://tiktok.com/@rshoprefills',   'description' => 'Public TikTok profile URL.'],
            ['key' => 'social.instagram', 'value' => 'https://instagram.com/rshoprefills', 'description' => 'Public Instagram profile URL.'],
        ];

        foreach ($rows as $row) {
            // Only seed if missing - never clobber an admin-edited value.
            if (! SiteSetting::query()->where('key', $row['key'])->exists()) {
                SiteSetting::put($row['key'], $row['value'], 'social', $row['description']);
            }
        }
    }

    public function down(): void
    {
        SiteSetting::query()
            ->whereIn('key', ['social.facebook', 'social.x', 'social.tiktok', 'social.instagram'])
            ->delete();
    }
};
