<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Currency / payment rates. One row per accepted currency (fiat or crypto).
     * `rate_per_usd` is "how many of this currency = 1 USD" — e.g. NGN 1400.0,
     * BTC 0.00001733. Used by the product detail "Estimated price" selector and
     * any future checkout flow that needs to convert order totals.
     */
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();   // ISO-ish: USD, NGN, BTC, USDT...
            $table->string('name');                  // "United States Dollar", "Bitcoin"
            $table->enum('type', ['fiat', 'crypto'])->index();
            $table->decimal('rate_per_usd', 24, 8); // multiplier from USD → this currency
            $table->string('icon_path')->nullable(); // public/assets/{filename}
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
