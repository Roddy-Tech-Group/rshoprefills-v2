<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Admin-set USD price that bypasses the CartPricingService rate
            // chain when present. NULL means "use the rules" (default).
            $table->decimal('manual_retail_price_usd', 12, 4)->nullable()->after('retail_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('manual_retail_price_usd');
        });
    }
};
