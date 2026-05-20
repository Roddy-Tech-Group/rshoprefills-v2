<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration.
 *
 * `2026_05_13_010000_add_google_auth_columns_to_users_table` was marked as
 * "Ran" in the migrations table on some developer DBs, but the `avatar_url`
 * column did not actually land — most likely because the column was dropped
 * manually via SQL after the migration ran, or because a partial schema
 * reset left the migrations row but cleared the column.
 *
 * Symptoms: avatar save 500's with
 *   SQLSTATE[42S22]: Column not found: 'avatar_url' in 'field list'
 *
 * This migration is idempotent — it only adds the column if it is missing,
 * so it's a no-op on DBs where everything is already in order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'avatar_url')) {
                // Mirror the original column shape so existing code (User model
                // fillable, GoogleAuthService, admin views) keeps working.
                $table->string('avatar_url')
                    ->nullable()
                    ->after(Schema::hasColumn('users', 'google_id') ? 'google_id' : 'email');
            }
        });
    }

    public function down(): void
    {
        // Intentionally a no-op: this migration only repairs missing state;
        // rolling it back would compete with the original migration's down().
    }
};
