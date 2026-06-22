<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('metadata');
            $table->string('category', 50)->nullable()->after('campaign_id');

            $table->index('campaign_id', 'idx_campaign_id');
            $table->index('category', 'idx_category');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_campaign_id');
            $table->dropIndex('idx_category');
            $table->dropColumn(['campaign_id', 'category']);
        });
    }
};
