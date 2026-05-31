<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Comprehensive seed of admin-editable site settings so future copy / branding
 * / SEO / contact updates ship from the System Settings UI instead of code
 * deploys. Idempotent: only seeds rows that don't already exist, so re-running
 * never clobbers an admin edit.
 *
 * Groups (in admin nav order):
 *   site       - name, tagline, legal entity
 *   seo        - meta tags, OG image, analytics IDs
 *   contact    - public-facing emails / phones (also surface on contact form)
 *   trust      - about-us / trust-strip copy + numbers
 *   system     - footer copyright, version, maintenance flag
 *   email      - default sender identity for transactional mail
 *   social     - extra channels beyond the four seeded earlier
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            // ── Site identity ────────────────────────────────────────────
            ['key' => 'site.name',            'group' => 'site',    'value' => 'RshopRefills',                                              'description' => 'Public-facing brand name. Used in <title> tags and email headers.'],
            ['key' => 'site.tagline',         'group' => 'site',    'value' => 'Gift cards, eSIMs, top-ups and bills - one wallet.',         'description' => 'One-line marketing tagline. Falls under the brand in headers and meta.'],
            ['key' => 'site.legal_name',      'group' => 'site',    'value' => 'Roddy Technologies LTD',                                    'description' => 'Registered legal entity that owns the brand. Appears in footers + legal pages.'],
            ['key' => 'site.legal_domain',    'group' => 'site',    'value' => 'RshopRefill.com',                                           'description' => 'Canonical domain shown in copyright lines and emails.'],
            ['key' => 'site.registration_id', 'group' => 'site',    'value' => '',                                                          'description' => 'Company registration number (CIPC / Companies House / etc.). Shown in legal footers when set.'],

            // ── SEO ─────────────────────────────────────────────────────
            ['key' => 'seo.default_title',         'group' => 'seo', 'value' => 'RshopRefills - Gift Cards, eSIMs, Top-ups & Bill Payments',  'description' => 'Default <title> fallback when a page does not set its own.'],
            ['key' => 'seo.default_description',   'group' => 'seo', 'value' => 'Buy gift cards, eSIMs, mobile top-ups and pay bills worldwide. Instant delivery, secure payments, 24/7 support.', 'description' => 'Default <meta name="description">. Keep under 160 characters.'],
            ['key' => 'seo.default_keywords',      'group' => 'seo', 'value' => 'gift cards, esim, mobile top-up, bill payment, digital marketplace', 'description' => 'Comma-separated keywords for the homepage meta.'],
            ['key' => 'seo.og_image_url',          'group' => 'seo', 'value' => '/assets/og-image.jpg',                                       'description' => 'Open Graph + Twitter card preview image (1200x630). Absolute URL or /-prefixed path.'],
            ['key' => 'seo.twitter_handle',        'group' => 'seo', 'value' => '@rshoprefills',                                              'description' => 'Twitter handle used for OG cards. Include the @.'],
            ['key' => 'seo.canonical_base_url',    'group' => 'seo', 'value' => 'https://rshoprefills.com',                                   'description' => 'Production canonical base. Used for absolute URLs in sitemaps and emails.'],
            ['key' => 'seo.robots_default',        'group' => 'seo', 'value' => 'index, follow',                                              'description' => 'Default robots meta. Use "noindex, nofollow" to delist the whole site.'],
            ['key' => 'seo.google_verification',   'group' => 'seo', 'value' => '',                                                           'description' => 'google-site-verification meta value. Leave blank to omit the tag.'],
            ['key' => 'seo.google_analytics_id',   'group' => 'seo', 'value' => '',                                                           'description' => 'GA4 measurement ID (G-XXXXXXX). Blank disables analytics.'],
            ['key' => 'seo.google_tag_manager_id', 'group' => 'seo', 'value' => '',                                                           'description' => 'GTM container ID (GTM-XXXXXXX). Blank disables GTM injection.'],
            ['key' => 'seo.facebook_pixel_id',     'group' => 'seo', 'value' => '',                                                           'description' => 'Facebook/Meta pixel ID. Blank disables the pixel.'],

            // ── Contact / Support emails + phones ─────────────────────────
            ['key' => 'contact.email_support',  'group' => 'contact', 'value' => 'support@rshoprefills.com',  'description' => 'Primary support address. Shown on the public contact page.'],
            ['key' => 'contact.email_info',     'group' => 'contact', 'value' => 'info@rshoprefills.com',     'description' => 'General info inbox. Shown on the contact page when set.'],
            ['key' => 'contact.email_dev',      'group' => 'contact', 'value' => '',                          'description' => 'Developer / integrations contact. Hidden from contact page when blank.'],
            ['key' => 'contact.email_sales',    'group' => 'contact', 'value' => '',                          'description' => 'Sales / partnerships contact. Hidden when blank.'],
            ['key' => 'contact.email_billing',  'group' => 'contact', 'value' => '',                          'description' => 'Billing / refunds contact. Hidden when blank.'],
            ['key' => 'contact.phone_primary',  'group' => 'contact', 'value' => '+237 676 700 173',          'description' => 'Primary phone number (E.164 format). Used for the contact page + emails.'],
            ['key' => 'contact.whatsapp_number', 'group' => 'contact', 'value' => '237676700173',              'description' => 'WhatsApp number without +. Powers wa.me links on the contact + support widgets.'],
            ['key' => 'contact.address',        'group' => 'contact', 'value' => '',                          'description' => 'Optional physical address. Shown on the contact page when set.'],
            ['key' => 'contact.support_hours',  'group' => 'contact', 'value' => '24/7',                      'description' => 'Support availability copy ("24/7", "Mon-Fri 9-5 GMT", etc.).'],
            ['key' => 'contact.notify_admin_email', 'group' => 'contact', 'value' => 'support@rshoprefills.com', 'description' => 'Inbox that receives contact-form + feedback notifications. Falls back to support if blank.'],

            // ── Department contact tiles (shown on the contact page when set) ──
            ['key' => 'contact.email_partnerships',  'group' => 'contact', 'value' => '', 'description' => 'Partnerships inbox. Surfaces a "Partnerships" tile on the contact page when set.'],
            ['key' => 'contact.url_partnerships_form', 'group' => 'contact', 'value' => '', 'description' => 'Optional partnerships inquiry-form URL. Shown under the partnerships email when set.'],
            ['key' => 'contact.email_suppliers',     'group' => 'contact', 'value' => '', 'description' => 'Suppliers inbox. Surfaces a "Suppliers" tile when set.'],
            ['key' => 'contact.url_suppliers_form',  'group' => 'contact', 'value' => '', 'description' => 'Optional suppliers inquiry-form URL.'],
            ['key' => 'contact.email_careers',       'group' => 'contact', 'value' => '', 'description' => 'Careers / HR inbox. Surfaces a "Careers" tile when set.'],
            ['key' => 'contact.email_press',         'group' => 'contact', 'value' => '', 'description' => 'Press / media inbox. Surfaces a "Press & media" tile when set.'],
            ['key' => 'contact.email_legal',         'group' => 'contact', 'value' => '', 'description' => 'Legal / compliance inbox. Optional tile when set.'],
            ['key' => 'contact.email_abuse',         'group' => 'contact', 'value' => '', 'description' => 'Abuse / fraud-report inbox. Optional tile when set.'],

            // ── Trust / About copy ─────────────────────────────────────────
            ['key' => 'trust.years_in_business', 'group' => 'trust', 'value' => '5',                                                                                          'description' => 'Years operating. Shown in trust strips and the about page.'],
            ['key' => 'trust.customers_served',  'group' => 'trust', 'value' => '50000',                                                                                       'description' => 'Customers served headline number. Rounds in the UI.'],
            ['key' => 'trust.countries_served',  'group' => 'trust', 'value' => '190',                                                                                         'description' => 'Country coverage count for eSIMs and gift cards.'],
            ['key' => 'trust.uptime_percentage', 'group' => 'trust', 'value' => '99.9',                                                                                        'description' => 'Public uptime claim. Surfaces in trust strips.'],
            ['key' => 'about.short_description', 'group' => 'trust', 'value' => 'RshopRefills is your one-stop digital marketplace for gift cards, eSIMs, top-ups and bill payments.', 'description' => 'Short About blurb used in meta + mobile hero.'],
            ['key' => 'about.long_description',  'group' => 'trust', 'value' => '',                                                                                            'description' => 'Long About copy for the About page hero. HTML or plain text.'],
            ['key' => 'about.mission',           'group' => 'trust', 'value' => 'Make digital spending borderless, instant and affordable.',                                  'description' => 'Mission statement displayed on the About page.'],
            ['key' => 'about.founded_year',      'group' => 'trust', 'value' => '2021',                                                                                        'description' => 'Founding year. Used to compute "since YYYY" badges.'],
            // The Trustpilot aggregate already lives under reviews.aggregate.{rating,count}.

            // ── System (footer, version, maintenance) ─────────────────────
            ['key' => 'system.version',           'group' => 'system', 'value' => '2.0.0',                                                                                                            'description' => 'App version string shown in the footer.'],
            ['key' => 'system.footer_copyright',  'group' => 'system', 'value' => '© {year} RshopRefill.com. All rights reserved. RshopRefill is a wholly-owned product of Roddy Technologies LTD, Registered.', 'description' => 'Footer copyright line. Token {year} auto-renders the current year.'],
            ['key' => 'system.maintenance_mode',  'group' => 'system', 'value' => 'off',                                                                                                               'description' => 'Hard kill-switch for transactional writes (checkout, cart, fund-wallet, withdraw). Browsing stays open. Admins always bypass.'],
            ['key' => 'system.maintenance_message', 'group' => 'system', 'value' => 'We are running quick maintenance. Back shortly.',                                                                  'description' => 'Banner copy shown to customers when maintenance_mode is on.'],

            // ── Feature flags - per-feature kill-switches ─────────────────
            ['key' => 'features.checkout_enabled',      'group' => 'features', 'value' => 'on',  'description' => 'Master toggle for the entire checkout flow. Off = customers can browse + cart but cannot complete purchases.'],
            ['key' => 'features.wallet_funding_enabled', 'group' => 'features', 'value' => 'on',  'description' => 'Off disables wallet top-ups from the Fund Wallet modal. Existing balance still spends.'],
            ['key' => 'features.wallet_withdraw_enabled', 'group' => 'features', 'value' => 'on',  'description' => 'Off disables Rcoin -> cash withdrawal requests.'],
            ['key' => 'features.signup_enabled',        'group' => 'features', 'value' => 'on',  'description' => 'Off hides the Register tab on the auth modal so the platform is login-only.'],
            ['key' => 'features.guest_cart_enabled',    'group' => 'features', 'value' => 'on',  'description' => 'Off requires login before adding items to cart.'],
            ['key' => 'features.gift_cards_enabled',    'group' => 'features', 'value' => 'on',  'description' => 'Off hides the Gift Cards category storefront-wide.'],
            ['key' => 'features.esims_enabled',         'group' => 'features', 'value' => 'on',  'description' => 'Off hides the eSIMs category storefront-wide.'],
            ['key' => 'features.topups_enabled',        'group' => 'features', 'value' => 'on',  'description' => 'Off hides the Mobile Top-ups category storefront-wide.'],
            ['key' => 'features.bills_enabled',         'group' => 'features', 'value' => 'on',  'description' => 'Off hides the Bill Payments category storefront-wide.'],
            ['key' => 'features.newsletter_signup_enabled', 'group' => 'features', 'value' => 'on', 'description' => 'Off hides the footer + auth newsletter opt-in.'],
            ['key' => 'features.feedback_widget_enabled', 'group' => 'features', 'value' => 'on',  'description' => 'Off hides the right-edge feedback tab on the storefront.'],

            // ── Email sender identity ─────────────────────────────────────
            ['key' => 'email.from_name',          'group' => 'email', 'value' => 'RshopRefills',                              'description' => 'Display name on all transactional emails.'],
            ['key' => 'email.from_address',       'group' => 'email', 'value' => 'no-reply@rshoprefills.com',                 'description' => 'From address on all transactional emails.'],
            ['key' => 'email.reply_to',           'group' => 'email', 'value' => 'support@rshoprefills.com',                  'description' => 'Reply-to header so customer replies land in support.'],
            ['key' => 'email.signature_html',     'group' => 'email', 'value' => 'Thanks,<br>The RshopRefills team',          'description' => 'Sign-off block appended to transactional emails. HTML allowed.'],

            // ── Extra social channels (the core 4 are seeded earlier) ────
            ['key' => 'social.youtube',    'group' => 'social', 'value' => '',  'description' => 'YouTube channel URL. Hidden when blank.'],
            ['key' => 'social.linkedin',   'group' => 'social', 'value' => '',  'description' => 'LinkedIn company page URL. Hidden when blank.'],
            ['key' => 'social.telegram',   'group' => 'social', 'value' => '',  'description' => 'Telegram channel URL. Hidden when blank.'],
            ['key' => 'social.discord',    'group' => 'social', 'value' => '',  'description' => 'Discord invite URL. Hidden when blank.'],
        ];

        foreach ($rows as $row) {
            if (! SiteSetting::query()->where('key', $row['key'])->exists()) {
                SiteSetting::put($row['key'], $row['value'], $row['group'], $row['description']);
            }
        }
    }

    public function down(): void
    {
        SiteSetting::query()
            ->whereIn('group', ['site', 'seo', 'contact', 'trust', 'system', 'email'])
            ->orWhereIn('key', ['social.youtube', 'social.linkedin', 'social.telegram', 'social.discord'])
            ->delete();
    }
};
