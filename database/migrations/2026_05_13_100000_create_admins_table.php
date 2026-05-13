<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the admins table.
     *
     * This table is completely separate from the users table to enforce
     * clean multi-auth isolation. Admins authenticate through the 'admin'
     * guard and have their own session driver, so a compromised user
     * session can never escalate to admin privileges.
     *
     * role: stored as a string to allow future expansion (e.g. 'editor',
     * 'support') without needing a separate pivot table at this scale.
     *
     * is_active: soft-disabling mechanism — deactivated admins are
     * blocked by middleware even if they hold a valid session.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 30)->default('super_admin');
            $table->string('avatar_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
