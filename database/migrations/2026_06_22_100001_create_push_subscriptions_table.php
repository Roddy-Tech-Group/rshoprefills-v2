<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscribable_type', 50);
            $table->unsignedBigInteger('subscribable_id');
            $table->string('endpoint', 500)->unique();
            $table->string('p256dh_key', 255);
            $table->string('auth_token', 255);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['subscribable_type', 'subscribable_id'], 'subscribable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
