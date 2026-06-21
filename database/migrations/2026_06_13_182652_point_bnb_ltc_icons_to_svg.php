<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BNB and LTC pointed at white-monochrome PNGs (BNB.png / LTC.png) that render
 * as blank white discs on the dark checkout panel. The repo already ships
 * proper brand-coloured SVGs (gold Binance mark, blue Litecoin Ł) that read in
 * both light and dark mode - repoint the currency rows at those.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('currency_rates')->where('code', 'BNB')->update(['icon_path' => 'BNB.svg']);
        DB::table('currency_rates')->where('code', 'LTC')->update(['icon_path' => 'LTC.svg']);
    }

    public function down(): void
    {
        DB::table('currency_rates')->where('code', 'BNB')->update(['icon_path' => 'BNB.png']);
        DB::table('currency_rates')->where('code', 'LTC')->update(['icon_path' => 'LTC.png']);
    }
};
