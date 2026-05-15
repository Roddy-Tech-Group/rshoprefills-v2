<?php

/*
 * Override map for brand logos. When a brand has a handcrafted asset saved in
 * `public/assets/`, we prefer that over Zendit's `logo_url` (which is sometimes
 * a wide gray placeholder that renders poorly inside a card tile).
 *
 * Keys must match `products.brand_key` exactly (case-sensitive) — these are
 * the raw Zendit brand identifiers, NOT the friendly display names.
 *
 * Add a row here when you save a new brand logo to public/assets/.
 */

return [
    'logos' => [
        'Apple' => 'apple.png',
        'Amazon' => 'amazon.png',
        'AmazonPrime' => 'amazon.png',
        'GooglePlay' => 'googleplay.png',
        'Netflix' => 'netflix.webp',
        'Nintendo' => 'nintendo.webp',
        'NintendoSwitch' => 'nintendo.webp',
        'Playstation' => 'playstation.png',
        'Spotify' => 'spotify.webp',
        'Steam' => 'steam.png',
        'HuluPlus' => 'hulu.webp',
        'Twitch' => 'twitch.webp',
        'Xbox' => 'x-box_300x190.png',
        'XboxGamePass' => 'x-box_300x190.png',
        'XboxLiveGold' => 'x-box_300x190.png',
    ],
];
