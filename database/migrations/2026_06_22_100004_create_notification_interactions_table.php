<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_interactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('interaction_type', 20);
            $table->string('channel', 20);
            $table->json('metadata')->nullable();
            $table->timestamp('interacted_at');

            $table->index('notification_id');
            $table->index('campaign_id');
            $table->index(['user_id', 'interaction_type']);
            $table->index('interacted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_interactions');
    }
};
