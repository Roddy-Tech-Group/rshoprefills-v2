<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Seed the integration / API-key inventory used by the admin "API & Integrations"
 * page. Keys live under the `integrations` group; the second segment of the key
 * name groups them by provider in the UI (e.g. all `integration.flutterwave.*`
 * render under a "Flutterwave" card).
 *
 * IMPORTANT: storing keys in SiteSetting is the inventory + UI layer. The
 * runtime config/services.php still reads from .env first. To make a DB-stored
 * key actually take effect, edit config/services.php to fall back to
 * SiteSetting::get('integration.<provider>.<key>') when env() returns null.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            // ── Flutterwave (card / mobile money / bank) ─────────────────
            ['key' => 'integration.flutterwave.public_key',     'description' => 'Flutterwave public (FLWPUBK_*) key. Embedded in the client SDK.'],
            ['key' => 'integration.flutterwave.secret_key',     'description' => 'Flutterwave secret (FLWSECK_*) key. Server-side calls only - never expose.'],
            ['key' => 'integration.flutterwave.encryption_key', 'description' => 'Flutterwave hosted-checkout encryption key.'],
            ['key' => 'integration.flutterwave.webhook_secret', 'description' => 'Verifies the X-Verif-Hash header on Flutterwave webhooks.'],

            // ── NowPayments (crypto) ─────────────────────────────────────
            ['key' => 'integration.nowpayments.api_key',     'description' => 'NowPayments public API key.'],
            ['key' => 'integration.nowpayments.ipn_secret',  'description' => 'IPN HMAC secret used to verify NowPayments webhooks.'],

            // ── Zendit (gift card + bill payment supplier) ───────────────
            ['key' => 'integration.zendit.api_key',         'description' => 'Zendit production/sandbox API key. Powers vouchers + bill-payment offers.'],
            ['key' => 'integration.zendit.webhook_secret',  'description' => 'Optional Zendit webhook verification secret.'],

            // ── Airalo (eSIM supplier) ───────────────────────────────────
            ['key' => 'integration.airalo.client_id',       'description' => 'Airalo partner OAuth client ID.'],
            ['key' => 'integration.airalo.client_secret',   'description' => 'Airalo partner OAuth client secret.'],
            ['key' => 'integration.airalo.webhook_secret',  'description' => 'Airalo webhook signature secret.'],

            // ── Google OAuth (sign-in) ───────────────────────────────────
            ['key' => 'integration.google_oauth.client_id',     'description' => 'Google OAuth 2.0 client ID for "Continue with Google".'],
            ['key' => 'integration.google_oauth.client_secret', 'description' => 'Google OAuth 2.0 client secret.'],

            // ── Google Places (address autocomplete) ─────────────────────
            ['key' => 'integration.google_places.api_key', 'description' => 'Google Places autocomplete API key.'],

            // ── Cloudflare Turnstile (captcha) ───────────────────────────
            ['key' => 'integration.turnstile.site_key',   'description' => 'Cloudflare Turnstile public site key.'],
            ['key' => 'integration.turnstile.secret_key', 'description' => 'Cloudflare Turnstile server-side verification secret.'],

            // ── Resend (transactional email + newsletter) ────────────────
            ['key' => 'integration.resend.api_key',     'description' => 'Resend API key used by Mail + the Newsletter audience export.'],
            ['key' => 'integration.resend.audience_id', 'description' => 'Default Resend audience ID for newsletter sync.'],

            // ── WhatsApp Business Cloud API ──────────────────────────────
            ['key' => 'integration.whatsapp.phone_id',      'description' => 'WhatsApp Business phone number ID (Meta Cloud API).'],
            ['key' => 'integration.whatsapp.access_token',  'description' => 'WhatsApp Business permanent access token.'],
            ['key' => 'integration.whatsapp.verify_token',  'description' => 'Webhook verify token used by Meta to confirm the callback URL.'],

            // ── Trustpilot (reviews) ─────────────────────────────────────
            ['key' => 'integration.trustpilot.api_key',  'description' => 'Trustpilot Business Units API key used for review sync.'],
            ['key' => 'integration.trustpilot.profile_url', 'description' => 'Public Trustpilot profile URL linked from the trust badge.'],

            // ── Sentry (error monitoring) ────────────────────────────────
            ['key' => 'integration.sentry.dsn',     'description' => 'Sentry DSN. Blank = error reporting disabled.'],
            ['key' => 'integration.sentry.release', 'description' => 'Optional release identifier (defaults to git SHA at deploy).'],
        ];

        foreach ($rows as $row) {
            if (! SiteSetting::query()->where('key', $row['key'])->exists()) {
                SiteSetting::put($row['key'], '', 'integrations', $row['description']);
            }
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('group', 'integrations')->delete();
    }
};
