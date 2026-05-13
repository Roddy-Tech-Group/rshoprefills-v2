<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the 'avatar' column used by Roddy's frontend.
 *
 * The google_id and nullable password changes are already handled
 * by 2026_05_13_010000_add_google_auth_columns_to_users_table.php.
 * This migration only adds the additional avatar column that the
 * frontend references alongside the existing avatar_url column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('avatar_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar']);
        });
    }
};
