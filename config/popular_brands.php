<?php

/*
 * Curated "popular" brand list.
 *
 * Drives the home page "Popular Gift Cards" row AND floats these brands to the
 * top of the /gift-cards listing under the default (Popularity) sort.
 *
 * Values must match `products.brand_key` exactly. Order matters — it's the
 * display order on the home row.
 */

return [
    'keys' => [
        'Apple',
        'Xbox',
        'Steam',
        'GooglePlay',
        'Amazon',
        'Playstation',
        'Netflix',
    ],
];
