<?php

/*
 * ISO-3166 country code → ISO-4217 currency code.
 *
 * Used to decide which currency a product's price should DISPLAY in once a
 * country is picked. The actual conversion rate comes from the admin-managed
 * `currency_rates` table — a currency only converts if it exists AND is active
 * there. Any country not mapped here, or whose currency isn't configured in
 * Rate Management, falls back to USD (the catalog's native currency).
 */

return [
    'map' => [
        // North America
        'US' => 'USD', 'CA' => 'CAD', 'MX' => 'MXN',

        // Eurozone
        'AT' => 'EUR', 'BE' => 'EUR', 'HR' => 'EUR', 'CY' => 'EUR', 'EE' => 'EUR',
        'FI' => 'EUR', 'FR' => 'EUR', 'DE' => 'EUR', 'GR' => 'EUR', 'IE' => 'EUR',
        'IT' => 'EUR', 'LV' => 'EUR', 'LT' => 'EUR', 'LU' => 'EUR', 'MT' => 'EUR',
        'NL' => 'EUR', 'PT' => 'EUR', 'SK' => 'EUR', 'SI' => 'EUR', 'ES' => 'EUR',
        'AD' => 'EUR', 'MC' => 'EUR', 'VA' => 'EUR', 'SM' => 'EUR',

        // Rest of Europe
        'GB' => 'GBP', 'CH' => 'CHF', 'SE' => 'SEK', 'NO' => 'NOK', 'DK' => 'DKK',
        'PL' => 'PLN', 'CZ' => 'CZK', 'HU' => 'HUF', 'RO' => 'RON', 'BG' => 'BGN',
        'RS' => 'RSD', 'UA' => 'UAH', 'RU' => 'RUB', 'IS' => 'ISK', 'TR' => 'TRY',

        // Africa
        'NG' => 'NGN', 'GH' => 'GHS', 'KE' => 'KES', 'ZA' => 'ZAR', 'TZ' => 'TZS',
        'UG' => 'UGX', 'EG' => 'EGP', 'MA' => 'MAD', 'GM' => 'GMD', 'RW' => 'RWF',
        'ET' => 'ETB', 'ZM' => 'ZMW', 'MZ' => 'MZN', 'BW' => 'BWP', 'NA' => 'NAD',
        // CFA franc zone (Central + West Africa)
        'CM' => 'XAF', 'CF' => 'XAF', 'TD' => 'XAF', 'CG' => 'XAF', 'GQ' => 'XAF', 'GA' => 'XAF',
        'BJ' => 'XOF', 'BF' => 'XOF', 'CI' => 'XOF', 'GW' => 'XOF', 'ML' => 'XOF',
        'NE' => 'XOF', 'SN' => 'XOF', 'TG' => 'XOF', 'GN' => 'GNF',

        // Middle East
        'AE' => 'AED', 'SA' => 'SAR', 'QA' => 'QAR', 'BH' => 'BHD', 'KW' => 'KWD',
        'OM' => 'OMR', 'JO' => 'JOD', 'IL' => 'ILS', 'LB' => 'LBP', 'IQ' => 'IQD',

        // Asia
        'IN' => 'INR', 'CN' => 'CNY', 'JP' => 'JPY', 'KR' => 'KRW', 'TW' => 'TWD',
        'HK' => 'HKD', 'SG' => 'SGD', 'TH' => 'THB', 'VN' => 'VND', 'PH' => 'PHP',
        'ID' => 'IDR', 'MY' => 'MYR', 'PK' => 'PKR', 'BD' => 'BDT', 'LK' => 'LKR',
        'KZ' => 'KZT', 'KH' => 'KHR', 'LA' => 'LAK',

        // Oceania
        'AU' => 'AUD', 'NZ' => 'NZD', 'FJ' => 'FJD', 'PG' => 'PGK',

        // Latin America + Caribbean
        'BR' => 'BRL', 'AR' => 'ARS', 'CL' => 'CLP', 'CO' => 'COP', 'PE' => 'PEN',
        'UY' => 'UYU', 'PY' => 'PYG', 'BO' => 'BOB', 'VE' => 'VES', 'EC' => 'USD',
        'CR' => 'CRC', 'GT' => 'GTQ', 'HN' => 'HNL', 'NI' => 'NIO', 'PA' => 'PAB',
        'DO' => 'DOP', 'JM' => 'JMD', 'TT' => 'TTD', 'HT' => 'HTG',
    ],
];
