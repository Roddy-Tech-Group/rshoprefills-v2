<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin section permissions. The role stays as a quick preset + badge, but
 * a Super Admin can now tick exactly which sidebar sections each admin may
 * open from the admin edit form. NULL means "fall back to the role's default
 * preset" so existing admins keep working until explicitly customised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
