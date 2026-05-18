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
        Schema::create('payment_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_attempt_id')->index();
            $table->string('provider');
            $table->string('session_type');
            $table->string('status')->default('pending');
            $table->string('client_reference')->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->string('provider_transaction_id')->nullable();
            $table->decimal('amount', 15, 4);
            $table->string('currency');
            $table->string('display_currency');
            $table->decimal('exchange_rate_snapshot', 10, 4)->default(1.0000);
            $table->json('payment_payload')->nullable();
            $table->json('checkout_context')->nullable();
            $table->string('customer_email');
            $table->string('customer_ip')->nullable();
            $table->json('device_metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('payment_attempt_id')
                ->references('id')
                ->on('payment_attempts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_sessions');
    }
};
