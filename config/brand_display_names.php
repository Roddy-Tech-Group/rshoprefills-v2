<?php

/*
 * Customer-facing display names for catalog brands.
 *
 * Zendit's brand_key (e.g. "Apple", "Google", "MobileLegends") is what we
 * route by and store on Product, but for the storefront we sometimes want a
 * friendlier consumer-facing label (e.g. "Everything Apple", "Google Play").
 * Add mappings here as you discover brands that need rebranding.
 *
 * Any brand_key not listed here falls back to the raw brand_key with light
 * humanisation (CamelCase → spaced words).
 *
 * Keys are case-sensitive — match exactly what's in `products.brand_key`.
 */

return [
    'aliases' => [
        // Apple ecosystem — App Store, iTunes, Apple Music, Apple TV+ in one card.
        'Apple'        => 'Everything Apple',

        // Google ecosystem.
        'Google'       => 'Everything Google',
        'GooglePlay'   => 'Google Play',

        // Gaming.
        'PlayStation'  => 'PlayStation Store',
        'Xbox'         => 'Xbox Live',
        'Steam'        => 'Steam Wallet',
        'NintendoEshop' => 'Nintendo eShop',
        'MobileLegends' => 'Mobile Legends',
        'FreeFire'     => 'Free Fire',
        'PUBGMobile'   => 'PUBG Mobile',
        'Roblox'       => 'Roblox',

        // Streaming + commerce.
        'Spotify'      => 'Spotify Premium',
        'AmazonCom'    => 'Amazon.com',
        'BestBuy'      => 'Best Buy',
        'StubHub'      => 'StubHub',

        // Extend as needed — flag any brand on the listing that reads awkwardly.
    ],
];
