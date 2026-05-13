<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Google OAuth columns and makes password nullable.
     *
     * google_id: unique identifier from Google — allows finding users
     * by their Google account. Nullable because credential-only users
     * won't have one until they link their Google account.
     *
     * avatar_url: Google profile picture URL. Nullable and optional.
     *
     * password: changed from NOT NULL to nullable to support users
     * who sign up exclusively through Google OAuth (no password needed).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar_url']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
