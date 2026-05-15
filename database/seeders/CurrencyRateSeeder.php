<?php

namespace Database\Seeders;

use App\Models\CurrencyRate;
use Illuminate\Database\Seeder;

class CurrencyRateSeeder extends Seeder
{
    /*
     * Seeds the currency_rates table with the launch set: 4 fiat + 6 crypto.
     * Rates expressed as "how much of this currency = 1 USD".
     *
     * Idempotent — uses updateOrCreate keyed on `code`. Re-run safely after a
     * `php artisan migrate:fresh` or to refresh rates without erasing admin
     * edits (it only writes the columns we explicitly list).
     */
    public function run(): void
    {
        $rows = [
            ['code' => 'GHS',  'name' => 'Ghanaian Cedi',           'type' => 'fiat',   'rate_per_usd' => 12.71,        'icon_path' => 'GH.svg',     'sort_order' => 10],
            ['code' => 'NGN',  'name' => 'Nigerian Naira',          'type' => 'fiat',   'rate_per_usd' => 1400.00,      'icon_path' => 'NGN.svg',    'sort_order' => 20],
            ['code' => 'USD',  'name' => 'United States Dollar',    'type' => 'fiat',   'rate_per_usd' => 1.04,         'icon_path' => null,         'sort_order' => 30],
            ['code' => 'XAF',  'name' => 'Central African CFA Franc', 'type' => 'fiat', 'rate_per_usd' => 788.00,       'icon_path' => 'XAF.svg',    'sort_order' => 40],

            ['code' => 'BNB',  'name' => 'BNB',                     'type' => 'crypto', 'rate_per_usd' => 0.001733,     'icon_path' => 'BNB.png',    'sort_order' => 110],
            ['code' => 'BTC',  'name' => 'Bitcoin',                 'type' => 'crypto', 'rate_per_usd' => 0.00001733,   'icon_path' => 'BTC.svg',    'sort_order' => 120],
            ['code' => 'ETH',  'name' => 'Ethereum',                'type' => 'crypto', 'rate_per_usd' => 0.0003467,    'icon_path' => 'ETH.svg',    'sort_order' => 130],
            ['code' => 'LTC',  'name' => 'Litecoin',                'type' => 'crypto', 'rate_per_usd' => 0.01156,      'icon_path' => 'LTC.png',    'sort_order' => 140],
            ['code' => 'SOL',  'name' => 'Solana',                  'type' => 'crypto', 'rate_per_usd' => 0.00693,      'icon_path' => 'SOLANA.svg', 'sort_order' => 150],
            ['code' => 'USDT', 'name' => 'Tether',                  'type' => 'crypto', 'rate_per_usd' => 1.03,         'icon_path' => 'USDT.svg',   'sort_order' => 160],
        ];

        foreach ($rows as $row) {
            CurrencyRate::updateOrCreate(
                ['code' => $row['code']],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}
