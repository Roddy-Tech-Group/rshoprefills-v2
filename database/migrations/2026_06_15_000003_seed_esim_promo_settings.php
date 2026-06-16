<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Editable copy for the three eSIM hero adverts (carousel on mobile, three
 * cards on desktop). Illustrations are fixed inline SVGs; only the heading and
 * supporting text are admin-editable on the System Settings page (group
 * "esim"). Seeded with the live default copy so the hero never renders blank
 * and the admin sees the current wording pre-filled.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['key' => 'esim.promo1_heading', 'value' => 'Feel the freedom of unlimited data', 'description' => 'eSIM hero advert 1 (travel scene) - heading.'],
            ['key' => 'esim.promo1_text', 'value' => 'Go ahead and watch that video, listen to that song, download that app. Explore our eSIM data packages for an uninterrupted connection in 190+ countries.', 'description' => 'eSIM hero advert 1 - supporting text.'],
            ['key' => 'esim.promo2_heading', 'value' => 'Stay connected the moment you land', 'description' => 'eSIM hero advert 2 (business / freelance) - heading.'],
            ['key' => 'esim.promo2_text', 'value' => 'From the airport gate to the boardroom, travellers, freelancers and remote teams get instant data in 190+ countries. No roaming bills, no SIM swaps.', 'description' => 'eSIM hero advert 2 - supporting text.'],
            ['key' => 'esim.promo3_heading', 'value' => 'Heading to the World Cup? Travel data sorted', 'description' => 'eSIM hero advert 3 (World Cup) - heading.'],
            ['key' => 'esim.promo3_text', 'value' => 'Follow every match across the host cities without hunting for WiFi. Activate a travel eSIM before you fly and stay online from kickoff to the final whistle.', 'description' => 'eSIM hero advert 3 - supporting text.'],
        ];

        foreach ($rows as $row) {
            if (! SiteSetting::query()->where('key', $row['key'])->exists()) {
                SiteSetting::put($row['key'], $row['value'], 'esim', $row['description']);
            }
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('group', 'esim')->delete();
    }
};
