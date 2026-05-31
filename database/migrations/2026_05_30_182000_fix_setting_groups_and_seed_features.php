<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Two fixes on top of 2026_05_30_181000_seed_comprehensive_site_settings.php:
 *
 * 1. Move rows mis-grouped as "general" into their intended groups. They got
 *    inserted earlier (before the comprehensive seeder existed) using
 *    SiteSetting::put()'s default group, then the comprehensive seeder's
 *    idempotent existence-check skipped them, leaving system.maintenance_mode
 *    sitting in "general" instead of "system" - which broke the ordered
 *    layout of the admin System Settings page.
 *
 * 2. Seed the features.* on/off flags (added to the comprehensive seeder after
 *    that seeder had already run on this DB, so they were never inserted).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Re-group misplaced rows ────────────────────────────────────
        $regroupings = [
            'system.maintenance_mode' => 'system',
            'site.registration_id' => 'site',
            'contact.address' => 'contact',
            'contact.email_abuse' => 'contact',
            'trust.countries_served' => 'trust',
            'about.founded_year' => 'trust',
        ];
        foreach ($regroupings as $key => $group) {
            SiteSetting::query()->where('key', $key)->update(['group' => $group]);
            Cache::forget('site_setting_'.$key);
        }

        // ── 2. Seed the features.* on/off flags if missing ────────────────
        $features = [
            ['key' => 'features.checkout_enabled',         'value' => 'on', 'description' => 'Master toggle for the entire checkout flow. Off = customers can browse + cart but cannot complete purchases.'],
            ['key' => 'features.wallet_funding_enabled',   'value' => 'on', 'description' => 'Off disables wallet top-ups from the Fund Wallet modal. Existing balance still spends.'],
            ['key' => 'features.wallet_withdraw_enabled',  'value' => 'on', 'description' => 'Off disables Rcoin -> cash withdrawal requests.'],
            ['key' => 'features.signup_enabled',           'value' => 'on', 'description' => 'Off hides the Register tab on the auth modal so the platform is login-only.'],
            ['key' => 'features.guest_cart_enabled',       'value' => 'on', 'description' => 'Off requires login before adding items to cart.'],
            ['key' => 'features.gift_cards_enabled',       'value' => 'on', 'description' => 'Off hides the Gift Cards category storefront-wide.'],
            ['key' => 'features.esims_enabled',            'value' => 'on', 'description' => 'Off hides the eSIMs category storefront-wide.'],
            ['key' => 'features.topups_enabled',           'value' => 'on', 'description' => 'Off hides the Mobile Top-ups category storefront-wide.'],
            ['key' => 'features.bills_enabled',            'value' => 'on', 'description' => 'Off hides the Bill Payments category storefront-wide.'],
            ['key' => 'features.newsletter_signup_enabled', 'value' => 'on', 'description' => 'Off hides the footer + auth newsletter opt-in.'],
            ['key' => 'features.feedback_widget_enabled',  'value' => 'on', 'description' => 'Off hides the right-edge feedback tab on the storefront.'],
        ];

        foreach ($features as $row) {
            if (! SiteSetting::query()->where('key', $row['key'])->exists()) {
                SiteSetting::put($row['key'], $row['value'], 'features', $row['description']);
            }
        }
    }

    public function down(): void
    {
        // Revert mis-grouped rows back to the "general" bucket they originally lived in.
        SiteSetting::query()
            ->whereIn('key', [
                'system.maintenance_mode',
                'site.registration_id',
                'contact.address',
                'contact.email_abuse',
                'trust.countries_served',
                'about.founded_year',
            ])
            ->update(['group' => 'general']);

        SiteSetting::query()->where('group', 'features')->delete();
    }
};
