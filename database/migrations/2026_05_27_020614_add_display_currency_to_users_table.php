<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Customer-selected display currency. Null means "auto" — the app
            // falls back to the user's primary wallet currency, then USD. Kept
            // as VARCHAR(10) for parity with wallets.currency / orders.display_currency.
            $table->string('display_currency', 10)->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('display_currency');
        });
    }
};
