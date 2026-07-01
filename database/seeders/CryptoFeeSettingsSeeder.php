<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin-configurable crypto fee settings into the settings table.
 * Safe to re-run: uses Setting::set() which is updateOrCreate under the hood.
 */
class CryptoFeeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // NOWPayments service fee rate (percentage)
        Setting::set(
            'crypto_service_fee_pct',
            0.5,
            'number',
            'NOWPayments service fee percentage (deducted from payment when is_fee_paid_by_user is false, added on top when true).'
        );

        // Per-network fee estimates (USD). These are advisory estimates shown
        // to the customer before invoice creation. Actual fees are determined
        // by blockchain conditions at transaction time.
        $networkFees = [
            'tron' => [1.00, 'Estimated TRON (TRC-20) network fee in USD.'],
            'ethereum' => [5.00, 'Estimated Ethereum (ERC-20) network fee in USD.'],
            'bnb' => [0.50, 'Estimated BNB (BEP-20) network fee in USD.'],
            'polygon' => [0.10, 'Estimated Polygon network fee in USD.'],
            'solana' => [0.05, 'Estimated Solana network fee in USD.'],
            'bitcoin' => [3.00, 'Estimated Bitcoin network fee in USD.'],
            'litecoin' => [0.10, 'Estimated Litecoin network fee in USD.'],
        ];

        foreach ($networkFees as $network => [$fee, $description]) {
            Setting::set("crypto_network_fee_{$network}", $fee, 'number', $description);
        }
    }
}
