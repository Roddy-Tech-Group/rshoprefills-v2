<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-user cashback multiplier. 1.00 = standard, 2.00 = 2× earner
            // (e.g. influencer / heavily-engaged customer), 0.50 = half earnings
            // (e.g. flagged account on watch). Applied by RewardEngine on both
            // cashback AND referral credits - power users earn proportionally
            // more for the same activity AND for any referrals they bring in.
            // Admin edits this from the customer detail page.
            $table->decimal('rcoin_multiplier', 4, 2)->default(1.00)->after('display_currency');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rcoin_multiplier');
        });
    }
};
