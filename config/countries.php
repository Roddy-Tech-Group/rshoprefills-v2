<?php

/**
 * Country name → ISO 3166-1 alpha-2 code lookup.
 *
 * Used by the locale modal to map the user's chosen country (full display
 * name) to the 2-letter code that Product.country_code uses for filtering.
 *
 * Access via: config('countries.codes')[$name] ?? null
 */

return [
    'codes' => [
        // A
        'Afghanistan' => 'AF', 'Albania' => 'AL', 'Algeria' => 'DZ', 'Andorra' => 'AD', 'Angola' => 'AO',
        'Antigua and Barbuda' => 'AG', 'Argentina' => 'AR', 'Armenia' => 'AM', 'Australia' => 'AU',
        'Austria' => 'AT', 'Azerbaijan' => 'AZ',

        // B
        'Bahamas' => 'BS', 'Bahrain' => 'BH', 'Bangladesh' => 'BD', 'Barbados' => 'BB', 'Belarus' => 'BY',
        'Belgium' => 'BE', 'Belize' => 'BZ', 'Benin' => 'BJ', 'Bhutan' => 'BT', 'Bolivia' => 'BO',
        'Bosnia and Herzegovina' => 'BA', 'Botswana' => 'BW', 'Brazil' => 'BR', 'Brunei' => 'BN',
        'Bulgaria' => 'BG', 'Burkina Faso' => 'BF', 'Burundi' => 'BI',

        // C
        'Cambodia' => 'KH', 'Cameroon' => 'CM', 'Canada' => 'CA', 'Cape Verde' => 'CV',
        'Central African Republic' => 'CF', 'Chad' => 'TD', 'Chile' => 'CL', 'China' => 'CN',
        'Colombia' => 'CO', 'Comoros' => 'KM', 'Congo (Brazzaville)' => 'CG', 'Congo (Kinshasa)' => 'CD',
        'Costa Rica' => 'CR', 'Côte d\'Ivoire' => 'CI', 'Croatia' => 'HR', 'Cuba' => 'CU',
        'Cyprus' => 'CY', 'Czechia' => 'CZ',

        // D
        'Denmark' => 'DK', 'Djibouti' => 'DJ', 'Dominica' => 'DM', 'Dominican Republic' => 'DO',

        // E
        'Ecuador' => 'EC', 'Egypt' => 'EG', 'El Salvador' => 'SV', 'Equatorial Guinea' => 'GQ',
        'Eritrea' => 'ER', 'Estonia' => 'EE', 'Eswatini' => 'SZ', 'Ethiopia' => 'ET',

        // F
        'Fiji' => 'FJ', 'Finland' => 'FI', 'France' => 'FR',

        // G
        'Gabon' => 'GA', 'Gambia' => 'GM', 'Georgia' => 'GE', 'Germany' => 'DE', 'Ghana' => 'GH',
        'Greece' => 'GR', 'Grenada' => 'GD', 'Guatemala' => 'GT', 'Guinea' => 'GN',
        'Guinea-Bissau' => 'GW', 'Guyana' => 'GY',

        // H
        'Haiti' => 'HT', 'Honduras' => 'HN', 'Hungary' => 'HU',

        // I
        'Iceland' => 'IS', 'India' => 'IN', 'Indonesia' => 'ID', 'Iraq' => 'IQ',
        'Ireland' => 'IE', 'Italy' => 'IT',

        // J
        'Jamaica' => 'JM', 'Japan' => 'JP', 'Jordan' => 'JO',

        // K
        'Kazakhstan' => 'KZ', 'Kenya' => 'KE', 'Kiribati' => 'KI', 'Kuwait' => 'KW', 'Kyrgyzstan' => 'KG',

        // L
        'Laos' => 'LA', 'Latvia' => 'LV', 'Lebanon' => 'LB', 'Lesotho' => 'LS', 'Liberia' => 'LR',
        'Libya' => 'LY', 'Liechtenstein' => 'LI', 'Lithuania' => 'LT', 'Luxembourg' => 'LU',

        // M
        'Madagascar' => 'MG', 'Malawi' => 'MW', 'Malaysia' => 'MY', 'Maldives' => 'MV', 'Mali' => 'ML',
        'Malta' => 'MT', 'Marshall Islands' => 'MH', 'Mauritania' => 'MR', 'Mauritius' => 'MU',
        'Mexico' => 'MX', 'Micronesia' => 'FM', 'Moldova' => 'MD', 'Monaco' => 'MC', 'Mongolia' => 'MN',
        'Montenegro' => 'ME', 'Morocco' => 'MA', 'Mozambique' => 'MZ', 'Myanmar' => 'MM',

        // N
        'Namibia' => 'NA', 'Nauru' => 'NR', 'Nepal' => 'NP', 'Netherlands' => 'NL', 'New Zealand' => 'NZ',
        'Nicaragua' => 'NI', 'Niger' => 'NE', 'Nigeria' => 'NG', 'North Macedonia' => 'MK', 'Norway' => 'NO',

        // O
        'Oman' => 'OM',

        // P
        'Pakistan' => 'PK', 'Palau' => 'PW', 'Panama' => 'PA', 'Papua New Guinea' => 'PG',
        'Paraguay' => 'PY', 'Peru' => 'PE', 'Philippines' => 'PH', 'Poland' => 'PL', 'Portugal' => 'PT',

        // Q
        'Qatar' => 'QA',

        // R
        'Romania' => 'RO', 'Rwanda' => 'RW',

        // S
        'Saint Kitts and Nevis' => 'KN', 'Saint Lucia' => 'LC', 'Saint Vincent and the Grenadines' => 'VC',
        'Samoa' => 'WS', 'San Marino' => 'SM', 'São Tomé and Príncipe' => 'ST', 'Saudi Arabia' => 'SA',
        'Senegal' => 'SN', 'Serbia' => 'RS', 'Seychelles' => 'SC', 'Sierra Leone' => 'SL',
        'Singapore' => 'SG', 'Slovakia' => 'SK', 'Slovenia' => 'SI', 'Solomon Islands' => 'SB',
        'Somalia' => 'SO', 'South Africa' => 'ZA', 'South Korea' => 'KR', 'South Sudan' => 'SS',
        'Spain' => 'ES', 'Sri Lanka' => 'LK', 'Sudan' => 'SD', 'Suriname' => 'SR', 'Sweden' => 'SE',
        'Switzerland' => 'CH',

        // T
        'Tajikistan' => 'TJ', 'Tanzania' => 'TZ', 'Thailand' => 'TH', 'Timor-Leste' => 'TL',
        'Togo' => 'TG', 'Tonga' => 'TO', 'Trinidad and Tobago' => 'TT', 'Tunisia' => 'TN',
        'Turkey' => 'TR', 'Turkmenistan' => 'TM', 'Tuvalu' => 'TV',

        // U
        'Uganda' => 'UG', 'Ukraine' => 'UA', 'United Arab Emirates' => 'AE', 'United Kingdom' => 'GB',
        'United States' => 'US', 'Uruguay' => 'UY', 'Uzbekistan' => 'UZ',

        // V
        'Vanuatu' => 'VU', 'Vatican City' => 'VA', 'Venezuela' => 'VE', 'Vietnam' => 'VN',

        // Y
        'Yemen' => 'YE',

        // Z
        'Zambia' => 'ZM', 'Zimbabwe' => 'ZW',
    ],
];
