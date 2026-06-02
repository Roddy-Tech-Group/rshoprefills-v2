<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'zendit' => [
        'base_url' => env('ZENDIT_BASE_URL'),
        'api_key' => env('ZENDIT_API_KEY'),
        'secret' => env('ZENDIT_SECRET'),
    ],

    'airalo' => [
        'base_url' => env('AIRALO_BASE_URL', 'https://sandbox-partners-api.airalo.com/v2'),
        'client_id' => env('AIRALO_CLIENT_ID'),
        'client_secret' => env('AIRALO_CLIENT_SECRET'),
        // Optional HMAC secret for webhook verification. Airalo only issues
        // partners a client_id + client_secret, so leaving this null is the
        // common case — the controller falls back to AIRALO_CLIENT_SECRET.
        // Only set this if you're on a custom Airalo signing arrangement.
        'webhook_secret' => env('AIRALO_WEBHOOK_SECRET'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@rshoprefills.com'),
        'from_name' => env('MAIL_FROM_NAME', 'RshopRefills'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'flutterwave' => [
        'public_key' => env('FLW_PUBLIC_KEY'),
        'secret_key' => env('FLW_SECRET_KEY'),
        'encryption_key' => env('FLW_ENCRYPTION_KEY'),
        'webhook_hash' => env('FLW_WEBHOOK_HASH'),
    ],

    'nowpayments' => [
        'api_key' => env('NOWPAYMENTS_API_KEY'),
        'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),
        'base_url' => env('NOWPAYMENTS_BASE_URL', 'https://api.nowpayments.io/v1'),
    ],

    'trustpilot' => [
        // Public IDs from the Trustpilot widget snippet (Share & promote -> Widgets).
        // Safe to commit defaults since the same values would render in HTML anyway.
        'business_unit_id' => env('TRUSTPILOT_BUSINESS_UNIT_ID', '6a09a40f1ccabec3e9c1b491'),
        'review_collector_template_id' => env('TRUSTPILOT_REVIEW_COLLECTOR_TEMPLATE_ID', '56278e9abfbbba0bdcd568bc'),
        'review_collector_token' => env('TRUSTPILOT_REVIEW_COLLECTOR_TOKEN', 'cfe1b67e-5fc4-4d20-9666-cc4ca9b37b87'),
        'profile_url' => env('TRUSTPILOT_PROFILE_URL', 'https://www.trustpilot.com/review/rshoprefill.com'),
        'short_link' => env('TRUSTPILOT_SHORT_LINK', 'https://trstp.lt/WMNWs7ZKQx'),
        'locale' => env('TRUSTPILOT_LOCALE', 'en-US'),
    ],

    'google_reviews' => [
        // Direct review URL from Google Business Profile -> "Get more reviews".
        // Used for the "Review us on Google" CTA button on /reviews.
        'review_url' => env('GOOGLE_REVIEW_URL', 'https://g.page/r/CZeYrL5rBXvuEAE/review'),
        // QR code rendered next to the CTA so desktop visitors can scan with
        // their phone. File lives in public/assets.
        'qr_asset' => env('GOOGLE_REVIEW_QR_ASSET', 'google reviews qr cord.webp'),
        // Optional: Place ID + Places API key for fetching live Google reviews
        // (paths 2/3 in the integration plan). Leave null until set up.
        'place_id' => env('GOOGLE_PLACE_ID'),
        'places_api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', false),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'enforce_checkout' => env('TURNSTILE_ENFORCE_CHECKOUT', true),
        'enforce_auth' => env('TURNSTILE_ENFORCE_AUTH', true),
        'enforce_contact' => env('TURNSTILE_ENFORCE_CONTACT', true),
        'bypass_local' => env('TURNSTILE_BYPASS_LOCAL', true),
    ],

];
