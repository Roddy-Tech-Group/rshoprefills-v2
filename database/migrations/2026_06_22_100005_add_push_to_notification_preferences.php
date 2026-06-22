<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('push_enabled')->default(true)->after('security_notifications');
            $table->boolean('engagement_enabled')->default(true)->after('push_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn(['push_enabled', 'engagement_enabled']);
        });
    }
};
