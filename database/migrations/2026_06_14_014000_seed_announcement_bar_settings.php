<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Three rotating announcement / coupon slots shown in the blue storefront top
 * bar. Each is free text the admin sets on the System Settings page (e.g.
 * "Use LAUNCHV2 to get 9% off"); blank slots are skipped and an all-blank set
 * hides the bar. Seeded empty so nothing shows until an admin fills one in.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['key' => 'announcement.promo_1', 'description' => 'Storefront announcement bar - slot 1. Free text (e.g. "Use LAUNCHV2 to get 9% off"). Blank = hidden.'],
            ['key' => 'announcement.promo_2', 'description' => 'Storefront announcement bar - slot 2. Blank = hidden. The bar rotates through the filled slots.'],
            ['key' => 'announcement.promo_3', 'description' => 'Storefront announcement bar - slot 3. Blank = hidden.'],
        ];

        foreach ($rows as $row) {
            if (! SiteSetting::query()->where('key', $row['key'])->exists()) {
                SiteSetting::put($row['key'], '', 'announcement', $row['description']);
            }
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('group', 'announcement')->delete();
    }
};
