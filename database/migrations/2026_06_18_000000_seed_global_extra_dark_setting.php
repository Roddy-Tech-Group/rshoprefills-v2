<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Global Extra Dark default. When ON, every user (customer + admin) loads in the
 * true-black "Extra Dark" theme by default. Each user can still override it from
 * their own appearance controls - their saved theme / Extra Dark choice wins, so
 * the global value only decides the default for people who have not chosen.
 *
 * Lives in the `system` group so it renders as a toggle beside the other
 * operational kill-switches on the admin System Settings page. The theme engine
 * (resources/views/partials/theme-engine.blade.php) reads it as the fallback for
 * users with no explicit preference.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! SiteSetting::query()->where('key', 'system.global_extra_dark')->exists()) {
            SiteSetting::put(
                'system.global_extra_dark',
                'off',
                'system',
                'When on, every user defaults to the Extra Dark (pure black) theme on load. Users can still override it from their own appearance settings.',
            );
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('key', 'system.global_extra_dark')->delete();
    }
};
