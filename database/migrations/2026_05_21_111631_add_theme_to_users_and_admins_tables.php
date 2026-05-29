<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Each account stores its own light/dark/system preference so the choice
     * follows them across devices. Admins and customers are isolated by living
     * on separate tables (and separate auth guards).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->default('system')->after('avatar');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->string('theme', 10)->default('system')->after('avatar_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
