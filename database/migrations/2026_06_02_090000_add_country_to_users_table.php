<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account country - the user's own declared country (where their account says
 * they're from). This is a persistent profile attribute shown in the user's
 * info and to admins. It is NOT the regional shopping switcher (that lives in
 * the storefront locale modal and only changes the catalog/currency view).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
