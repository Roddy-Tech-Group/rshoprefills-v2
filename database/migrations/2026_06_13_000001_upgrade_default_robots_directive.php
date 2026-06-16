<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Upgrade the seeded SEO robots default from the bare "index, follow" to the
 * richer directive that explicitly permits large image previews and full text
 * snippets - both lift click-through on Google results. Only updates the row
 * when it still holds the original seed value, so an admin who deliberately
 * set their own directive (e.g. "noindex, nofollow" during maintenance) is
 * never overwritten.
 */
return new class extends Migration
{
    private string $rich = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    public function up(): void
    {
        $row = SiteSetting::query()->where('key', 'seo.robots_default')->first();

        if (! $row || $row->value === 'index, follow') {
            SiteSetting::put(
                'seo.robots_default',
                $this->rich,
                'seo',
                'Default robots meta. Use "noindex, nofollow" to delist the whole site.',
            );
        }
    }

    public function down(): void
    {
        $row = SiteSetting::query()->where('key', 'seo.robots_default')->first();

        if ($row && $row->value === $this->rich) {
            SiteSetting::put(
                'seo.robots_default',
                'index, follow',
                'seo',
                'Default robots meta. Use "noindex, nofollow" to delist the whole site.',
            );
        }
    }
};
