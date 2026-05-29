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
        // 1. In-App Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // Mail class, e.g. WelcomeMail
            $table->string('title');
            $table->text('message');
            $table->string('channel', 20); // email, database, etc.
            $table->string('status', 20)->default('pending'); // pending, sent, failed
            $table->string('priority', 20)->default('normal'); // normal, high, critical
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('channel');
        });

        // 2. Notification Delivery Audit Trail
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->string('provider', 30); // resend, database
            $table->string('channel', 20); // email, database
            $table->string('recipient'); // email, phone, user_id
            $table->string('status', 20); // sent, failed
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at')->useCurrent();

            $table->foreign('notification_id')->references('id')->on('notifications')->nullOnDelete();
            $table->index('notification_id');
            $table->index('status');
        });

        // 3. User Notification Preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('marketing_enabled')->default(true);
            $table->boolean('order_notifications')->default(true);
            $table->boolean('wallet_notifications')->default(true);
            $table->boolean('security_notifications')->default(true);
            $table->timestamps();
        });

        // 4. Newsletter Subscribers
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('status', 20)->default('active'); // active, unsubscribed
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 50)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
    }
};
