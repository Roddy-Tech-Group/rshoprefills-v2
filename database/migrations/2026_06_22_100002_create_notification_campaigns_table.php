<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('title', 255);
            $table->string('notification_title', 255);
            $table->text('notification_message');
            $table->string('notification_url', 500)->nullable();
            $table->json('channels');
            $table->string('category', 50);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('draft');
            $table->string('audience_type', 50);
            $table->json('audience_filters')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('recurrence_rule', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('stats_sent')->default(0);
            $table->unsignedInteger('stats_delivered')->default(0);
            $table->unsignedInteger('stats_failed')->default(0);
            $table->unsignedInteger('stats_opened')->default(0);
            $table->unsignedInteger('stats_clicked')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_at');
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_campaigns');
    }
};
