<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification_interactions', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->after('channel');
            $table->ipAddress('ip_address')->nullable()->after('user_agent');
            $table->timestamps();
            
            if (Schema::hasColumn('notification_interactions', 'interacted_at')) {
                if (Schema::hasIndex('notification_interactions', ['interacted_at'])) {
                    $table->dropIndex(['interacted_at']);
                }
                $table->dropColumn('interacted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_interactions', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'ip_address', 'created_at', 'updated_at']);
            $table->timestamp('interacted_at')->nullable();
        });
    }
};
